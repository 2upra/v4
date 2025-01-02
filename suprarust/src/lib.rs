#![allow(non_snake_case)]
use ext_php_rs::prelude::*;
use ext_php_rs::types::Zval;
use ext_php_rs::builders::ModuleBuilder;
use mysql::*;
use mysql::prelude::*;
use chrono::prelude::*;
use std::collections::HashMap;
use dotenv::dotenv;
use std::env;

// ... (resto de tus estructuras y funciones) ...

fn obtenerConexion() -> Result<PooledConn> {
     dotenv().ok(); // Carga las variables de entorno desde .env

// Estructuras para los datos de los posts y likes
#[derive(Debug, Default, Clone, PartialEq)]
struct PostData {
    id: u64,
    autor: u64,
    fecha: String,
    contenido: String,
}

#[derive(Debug, Default, Clone, PartialEq)]
struct LikeData {
    post_id: u64,
    like: u32,
    favorito: u32,
    no_me_gusta: u32,
}

#[derive(Debug, Default, Clone, PartialEq)]
struct MetaData {
    datosAlgoritmo: Option<String>,
    Verificado: Option<String>,
    postAut: Option<String>,
    artista: Option<bool>,
    fan: Option<bool>,
}

// Función para obtener la conexión a la base de datos
fn obtenerConexion() -> Result<PooledConn> {
     dotenv().ok(); // Carga las variables de entorno desde .env

     let db_user = env::var("DB_USER").expect("Variable DB_USER no encontrada en .env");
     let db_password = env::var("DB_PASSWORD").expect("Variable DB_PASSWORD no encontrada en .env");
     let db_host = env::var("DB_HOST").expect("Variable DB_HOST no encontrada en .env");
     let db_name = env::var("DB_NAME").expect("Variable DB_NAME no encontrada en .env");
     let db_port = env::var("DB_PORT").unwrap_or_else(|_| "3306".to_string()); // Puerto 3306 por defecto

     let url = format!("mysql://{}:{}@{}:{}/{}", db_user, db_password, db_host, db_port, db_name);

     let pool = Pool::new(url.as_str())?; // Convertir la String a &str
     pool.get_conn()
 }

#[php_function]
pub fn obtenerDatosFeedRust(usu: i64) -> PhpResult<Vec<Zval>> {
    let mut conn = obtenerConexion().unwrap();

    // --- Obtener 'siguiendo' ---
    let siguiendo: Vec<i64> = conn.query_map(
        format!("SELECT meta_value FROM wp_usermeta WHERE user_id = {} AND meta_key = 'siguiendo'", usu),
        |meta_value: String| {
            meta_value.parse().unwrap_or(0)
        },
    ).unwrap_or_else(|_| {
        vec![]
    });

    // --- Obtener 'intereses' ---
    let intereses: HashMap<String, i32> = conn.query_map(
        format!("SELECT interest, intensity FROM {} WHERE user_id = {}", "INTERES_TABLE", usu),
        |(interest, intensity)| (interest, intensity),
    ).unwrap_or_default().into_iter().collect();

    // --- Obtener 'vistas' (simulado, ya que no hay acceso directo a user_meta desde Rust) ---
    let vistas: Vec<i64> = Vec::new(); 

    // --- Obtener IDs de posts en los últimos 365 días ---
    let fechaLimite = (Utc::now() - chrono::Duration::days(365)).format("%Y-%m-%d").to_string();
    let postsIds: Vec<u64> = conn.query_map(
        format!("SELECT ID FROM wp_posts WHERE post_type = 'social_post' AND post_date > '{}'", fechaLimite),
        |id: u64| id,
    ).unwrap_or_default();

    // --- Obtener metadata de los posts ---
    let metaData: HashMap<u64, MetaData> = {
        let mut meta = HashMap::new();
        if !postsIds.is_empty() {
            let postsIdsStr = postsIds.iter().map(|id| id.to_string()).collect::<Vec<String>>().join(",");
            let metaRes: Vec<(u64, String, String)> = conn.query_map(
                format!("SELECT post_id, meta_key, meta_value FROM wp_postmeta WHERE post_id IN ({}) AND meta_key IN ('datosAlgoritmo', 'Verificado', 'postAut', 'artista', 'fan')", postsIdsStr),
                |(post_id, meta_key, meta_value)| (post_id, meta_key, meta_value),
            ).unwrap_or_default();

            for (post_id, meta_key, meta_value) in metaRes {
                let entry = meta.entry(post_id).or_insert_with(MetaData::default);
                match meta_key.as_str() {
                    "datosAlgoritmo" => entry.datosAlgoritmo = Some(meta_value),
                    "Verificado" => entry.Verificado = Some(meta_value),
                    "postAut" => entry.postAut = Some(meta_value),
                    "artista" => entry.artista = Some(meta_value == "1"),
                    "fan" => entry.fan = Some(meta_value == "1"),
                    _ => {}
                }
            }
        }
        meta
    };

    // --- Obtener likes de los posts ---
    let likesPorPost: HashMap<u64, LikeData> = {
        let mut likes = HashMap::new();
        if !postsIds.is_empty() {
            let postsIdsStr = postsIds.iter().map(|id| id.to_string()).collect::<Vec<String>>().join(",");
            let likesRes: Vec<(u64, String, u32)> = conn.query_map(
                format!("SELECT post_id, like_type, COUNT(*) as cantidad FROM wp_post_likes WHERE post_id IN ({}) GROUP BY post_id, like_type", postsIdsStr),
                |(post_id, like_type, cantidad)| (post_id, like_type, cantidad),
            ).unwrap_or_default();

            for (post_id, like_type, cantidad) in likesRes {
                let entry = likes.entry(post_id).or_insert_with(LikeData::default);
                match like_type.as_str() {
                    "like" => entry.like = cantidad,
                    "favorito" => entry.favorito = cantidad,
                    "no_me_gusta" => entry.no_me_gusta = cantidad,
                    _ => {}
                }
            }
        }
        likes
    };

    // --- Obtener datos de los posts ---
    let postContenido: HashMap<u64, String> = {
        let mut contenido = HashMap::new();
        if !postsIds.is_empty() {
            let postsIdsStr = postsIds.iter().map(|id| id.to_string()).collect::<Vec<String>>().join(",");
            let postsRes: Vec<(u64, String)> = conn.query_map(
                format!("SELECT ID, post_content FROM wp_posts WHERE ID IN ({})", postsIdsStr),
                |(id, post_content)| (id, post_content),
            ).unwrap_or_default();

            for (id, post_content) in postsRes {
                contenido.insert(id, post_content);
            }
        }
        contenido
    };

    // --- Preparar los resultados para PHP ---
    let mut resultado = vec![];
    for id in postsIds {
        let mut datos = vec![];
        datos.push(zval!(id));
        datos.push(zval!(siguiendo.clone()));
        datos.push(zval!(intereses.clone()));
        datos.push(zval!(vistas.clone()));
        datos.push(zval!(metaData.get(&id).cloned().unwrap_or_default()));
        datos.push(zval!(likesPorPost.get(&id).cloned().unwrap_or_default()));
        datos.push(zval!(postContenido.get(&id).cloned().unwrap_or_default()));
        resultado.push(zval!(datos));
    }

    Ok(resultado)
}

#[php_module]
pub fn module(module: ModuleBuilder) -> ModuleBuilder {
    module
}