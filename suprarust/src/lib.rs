#![allow(non_snake_case)]
use chrono::prelude::*;
use dotenv::dotenv;
use ext_php_rs::builders::ModuleBuilder;
use ext_php_rs::convert::IntoZval;
use ext_php_rs::ffi::HashTable; // Importar HashTable correctamente
use ext_php_rs::flags::DataType;
use ext_php_rs::prelude::*;
use ext_php_rs::types::Zval;
use mysql::prelude::*;
use mysql::*;
use serde::{Deserialize, Serialize};
use std::collections::HashMap;
// use std::convert::Infallible;  Eliminar esta línea, ya que no se usa
use serde_json;
use std::env;

// Estructuras para los datos de los posts y likes
#[derive(Debug, Default, Clone, PartialEq, Serialize, Deserialize)]
struct PostData {
    id: u64,
    autor: u64,
    fecha: String,
    contenido: String,
}

#[derive(Debug, Default, Clone, PartialEq, Serialize, Deserialize)]
struct LikeData {
    post_id: u64,
    like: u32,
    favorito: u32,
    nome_gusta: u32,
}

#[derive(Debug, Default, Clone, PartialEq, Serialize, Deserialize)]
struct MetaData {
    datosAlgoritmo: Option<String>,
    Verificado: Option<String>,
    postAut: Option<String>,
    artista: Option<bool>,
    fan: Option<bool>,
}

// Implementación correcta de IntoZval con manejo de errores
impl IntoZval for MetaData {
    const TYPE: DataType = DataType::Array;

    fn set_zval(self, zv: &mut Zval, persistent: bool) -> Result<(), ext_php_rs::error::Error>
    where
        Self: Sized,
    {
        let mut arr = HashTable::new();
        if let Some(datosAlgoritmo) = self.datosAlgoritmo {
            arr.insert("datosAlgoritmo", datosAlgoritmo, persistent)?;
        }
        if let Some(verificado) = self.Verificado {
            arr.insert("Verificado", verificado, persistent)?;
        }
        if let Some(postAut) = self.postAut {
            arr.insert("postAut", postAut, persistent)?;
        }
        arr.insert("artista", self.artista.unwrap_or(false), persistent)?;
        arr.insert("fan", self.fan.unwrap_or(false), persistent)?;

        zv.set_hashtable(arr);
        Ok(())
    }

    fn into_zval(self, persistent: bool) -> Result<Zval, ext_php_rs::error::Error>
    where
        Self: Sized,
    {
        let mut zv = Zval::new();
        self.set_zval(&mut zv, persistent)?;
        Ok(zv)
    }
}

impl IntoZval for LikeData {
    const TYPE: DataType = DataType::Array;

    fn set_zval(self, zv: &mut Zval, persistent: bool) -> Result<(), ext_php_rs::error::Error>
    where
        Self: Sized,
    {
        let mut arr = HashTable::new();
        arr.insert("post_id", self.post_id, persistent)?;
        arr.insert("like", self.like, persistent)?;
        arr.insert("favorito", self.favorito, persistent)?;
        arr.insert("no_me_gusta", self.nome_gusta, persistent)?;

        zv.set_hashtable(arr);
        Ok(())
    }

    fn into_zval(self, persistent: bool) -> Result<Zval, ext_php_rs::error::Error>
    where
        Self: Sized,
    {
        let mut zv = Zval::new();
        self.set_zval(&mut zv, persistent)?;
        Ok(zv)
    }
}

// Función para obtener la conexión a la base de datos
fn obtenerConexion() -> std::result::Result<PooledConn, mysql::Error> {
    dotenv().ok(); // Carga las variables de entorno desde .env

    let db_user = env::var("DB_USER").expect("Variable DB_USER no encontrada en .env");
    let db_password = env::var("DB_PASSWORD").expect("Variable DB_PASSWORD no encontrada en .env");
    let db_host = env::var("DB_HOST").expect("Variable DB_HOST no encontrada en .env");
    let db_name = env::var("DB_NAME").expect("Variable DB_NAME no encontrada en .env");
    let db_port = env::var("DB_PORT").unwrap_or_else(|_| "3306".to_string()); // Puerto 3306 por defecto

    let url = format!(
        "mysql://{}:{}@{}:{}/{}",
        db_user, db_password, db_host, db_port, db_name
    );

    let pool = Pool::new(url.as_str())?; // Convertir la String a &str
    pool.get_conn()
}

#[php_function]
pub fn obtenerDatosFeedRust(usu: i64) -> PhpResult<Vec<Zval>> {
    let mut conn = obtenerConexion().unwrap();

    // --- Obtener 'siguiendo' ---
    let siguiendo: Vec<i64> = conn
        .query_map(
            format!(
                "SELECT meta_value FROM wp_usermeta WHERE user_id = {} AND meta_key = 'siguiendo'",
                usu
            ),
            |meta_value: String| meta_value.parse().unwrap_or(0),
        )
        .unwrap_or_else(|_| vec![]);

    // --- Obtener 'intereses' ---
    let intereses: HashMap<String, i32> = conn
        .query_map(
            format!(
                "SELECT interest, intensity FROM {} WHERE user_id = {}",
                "INTERES_TABLE", usu
            ),
            |(interest, intensity)| (interest, intensity),
        )
        .unwrap_or_default()
        .into_iter()
        .collect();

    // --- Obtener 'vistas' (deserializando datos serializados de PHP) ---
    #[derive(Debug, Deserialize, Serialize)]
    struct Vista {
        count: i64,
        last_view: i64,
    }

    #[derive(Debug, Deserialize, Serialize)]
    struct VistasData(HashMap<i64, Vista>);

    // --- Corrección final en 'vistas' ---
    let vistas: Vec<i64> = conn.query_map(
     format!("SELECT meta_value FROM wp_usermeta WHERE user_id = {} AND meta_key = 'vistas_posts'", usu),
     |meta_value: String| {
         let parsed_vistas: std::result::Result<VistasData, serde_json::Error> = serde_json::from_str(&meta_value);
         match parsed_vistas {
             Ok(vistas_data) => vistas_data.0.into_iter().map(|(_, vista)| vista.count).collect(),
             Err(err) => {
                 eprintln!("Error al deserializar vistas_posts: {}", err);
                 vec![] // Devuelve un vector vacío en caso de error
             },
         }
     },
 ).unwrap_or_else(|err| {
     eprintln!("Error al obtener vistas_posts de la base de datos: {}", err);
     vec![] // Corrección aquí: Devolver directamente el vector vacío
 });

    // --- Obtener IDs de posts en los últimos 365 días ---
    let fechaLimite = (Utc::now() - chrono::Duration::days(365))
        .format("%Y-%m-%d")
        .to_string();
    let postsIds: Vec<u64> = conn
        .query_map(
            format!(
                "SELECT ID FROM wp_posts WHERE post_type = 'social_post' AND post_date > '{}'",
                fechaLimite
            ),
            |id: u64| id,
        )
        .unwrap_or_default();

    // --- Obtener metadata de los posts ---
    let metaData: HashMap<u64, MetaData> = {
        let mut meta = HashMap::new();
        if !postsIds.is_empty() {
            let postsIdsStr = postsIds
                .iter()
                .map(|id| id.to_string())
                .collect::<Vec<String>>()
                .join(",");
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
            let postsIdsStr = postsIds
                .iter()
                .map(|id| id.to_string())
                .collect::<Vec<String>>()
                .join(",");
            let likesRes: Vec<(u64, String, u32)> = conn.query_map(
                format!("SELECT post_id, like_type, COUNT(*) as cantidad FROM wp_post_likes WHERE post_id IN ({}) GROUP BY post_id, like_type", postsIdsStr),
                |(post_id, like_type, cantidad)| (post_id, like_type, cantidad),
            ).unwrap_or_default();

            for (post_id, like_type, cantidad) in likesRes {
                let entry = likes.entry(post_id).or_insert_with(LikeData::default);
                match like_type.as_str() {
                    "like" => entry.like = cantidad,
                    "favorito" => entry.favorito = cantidad,
                    "no_me_gusta" => entry.nome_gusta = cantidad,
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
            let postsIdsStr = postsIds
                .iter()
                .map(|id| id.to_string())
                .collect::<Vec<String>>()
                .join(",");
            let postsRes: Vec<(u64, String)> = conn
                .query_map(
                    format!(
                        "SELECT ID, post_content FROM wp_posts WHERE ID IN ({})",
                        postsIdsStr
                    ),
                    |(id, post_content)| (id, post_content),
                )
                .unwrap_or_default();

            for (id, post_content) in postsRes {
                contenido.insert(id, post_content);
            }
        }
        contenido
    };

    // --- Preparar los resultados para PHP ---
    let mut resultado: Vec<Zval> = vec![];
    for id in postsIds {
        let mut datos = vec![];
        datos.push(id.into_zval(false).unwrap());
        datos.push(siguiendo.into_zval(false).unwrap());
        datos.push(intereses.into_zval(false).unwrap());
        datos.push(vistas.into_zval(false).unwrap());
        datos.push(match metaData.get(&id).cloned() {
            Some(meta_data) => meta_data.into_zval(false).unwrap_or_default(),
            None => Zval::new(),
        });

        datos.push(match likesPorPost.get(&id).cloned() {
            Some(likes_data) => likes_data.into_zval(false).unwrap_or_default(),
            None => Zval::new(),
        });

        datos.push(match postContenido.get(&id).cloned() {
            Some(content) => content.into_zval(false).unwrap_or_default(),
            None => Zval::new(),
        });
        resultado.push(datos.into_zval(false).unwrap());
    }

    Ok(resultado)
}

#[php_module]
pub fn get_module(module: ModuleBuilder) -> ModuleBuilder {
    module
}
