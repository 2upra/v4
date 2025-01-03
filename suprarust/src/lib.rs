#![cfg_attr(windows, feature(abi_vectorcall))]
use ext_php_rs::convert::IntoZval;
use ext_php_rs::prelude::*;
use ext_php_rs::types::Zval;
use mysql::prelude::Queryable;
use mysql::Pool;
use mysql::*;
use std::collections::HashMap;
use std::env;
use std::path::Path;
use once_cell::sync::Lazy; // Importar once_cell

// Crear un pool de conexiones estático y global con Lazy
static GLOBAL_POOL: Lazy<Result<Pool, String>> = Lazy::new(|| {
    let project_dir = "/var/www/wordpress/wp-content/themes/2upra3v/suprarust";
    let env_path = Path::new(project_dir).join(".env");
    dotenv::from_path(&env_path).map_err(|e| format!("Error al cargar el archivo .env: {}", e))?;

    let db_user =
        env::var("DB_USER").map_err(|_| "Variable DB_USER no encontrada en .env".to_string())?;
    let db_password = env::var("DB_PASSWORD")
        .map_err(|_| "Variable DB_PASSWORD no encontrada en .env".to_string())?;
    let db_host =
        env::var("DB_HOST").map_err(|_| "Variable DB_HOST no encontrada en .env".to_string())?;
    let db_name =
        env::var("DB_NAME").map_err(|_| "Variable DB_NAME no encontrada en .env".to_string())?;
    let db_port = env::var("DB_PORT").unwrap_or_else(|_| "3306".to_string());

    let url = format!(
        "mysql://{}:{}@{}:{}/{}",
        db_user, db_password, db_host, db_port, db_name
    );

    Pool::new(url.as_str()).map_err(|e| format!("Error al crear el pool de conexiones: {}", e))
});

#[php_function]
pub fn conectar_bd_sin_lazy_static() -> String {
    // Usar el pool global
    match &*GLOBAL_POOL {
        Ok(pool) => match pool.get_conn() {
            Ok(mut conn) => match conn.query_drop("SELECT 1") {
                Ok(_) => "Conexión exitosa a la base de datos.".to_string(),
                Err(err) => format!("Error al ejecutar consulta de prueba: {}", err),
            },
            Err(err) => format!("Error al obtener conexión del pool: {}", err),
        },
        Err(err) => err.clone(),
    }
}

#[php_function]
pub fn solo_conectar_bd() -> String {
    // Usar el pool global
    match &*GLOBAL_POOL {
        Ok(_) => "Pool de conexiones creado con éxito.".to_string(),
        Err(err) => err.clone(),
    }
}

fn obtener_metadatos_posts_rust(
    conn: &mut PooledConn,
    posts_ids: Vec<i64>,
) -> Result<Zval, String> {
    let meta_keys = vec!["datosAlgoritmo", "Verificado", "postAut", "artista", "fan"];

    let (meta_data, logs) = match ejecutar_consulta(conn, posts_ids, meta_keys) {
        Ok((meta_data, logs)) => (meta_data, logs),
        Err(err) => return Err(err),
    };

    let mut res_map = HashMap::new();
    let mut meta_data_map = HashMap::new();

    for (post_id, meta_map) in meta_data {
        let mut post_meta_map = HashMap::new();
        for (key, value) in meta_map {
            post_meta_map.insert(key, value.into_zval(false).unwrap());
        }
        meta_data_map.insert(post_id.to_string(), post_meta_map.into_zval(false).unwrap());
    }
    res_map.insert(
        "meta_data".to_string(),
        meta_data_map.into_zval(false).unwrap(),
    );
    res_map.insert("logs".to_string(), logs.into_zval(false).unwrap());

    Ok(res_map.into_zval(false).unwrap())
}

#[php_function]
pub fn obtener_metadatos_con_conexion(posts_ids: Vec<i64>) -> Result<Zval, String> {
    // Usar el pool global
    let pool = match &*GLOBAL_POOL {
        Ok(pool) => pool,
        Err(err) => return Err(err.clone()),
    };

    let mut conn = match pool.get_conn() {
        Ok(conn) => conn,
        Err(err) => return Err(format!("Error al obtener la conexión: {}", err)),
    };

    obtener_metadatos_posts_rust(&mut conn, posts_ids)
}

fn ejecutar_consulta(
    conn: &mut PooledConn,
    posts_ids: Vec<i64>,
    meta_keys: Vec<&str>,
) -> Result<(HashMap<i64, HashMap<String, String>>, Vec<String>), String> {
    let mut logs = Vec::new();

    // Usar declaración preparada para evitar inyección SQL y mejorar el rendimiento
    let placeholders = posts_ids.iter().map(|_| "?").collect::<Vec<_>>().join(", ");
    let meta_keys_placeholders = meta_keys.iter().map(|_| "?").collect::<Vec<_>>().join(", ");

    let sql_meta = format!(
        "SELECT post_id, meta_key, meta_value
         FROM wpsg_postmeta
         WHERE meta_key IN ({}) AND post_id IN ({})",
        meta_keys_placeholders, placeholders
    );

    // Preparar la consulta
    let mut stmt = match conn.prep(sql_meta) {
        Ok(stmt) => stmt,
        Err(err) => return Err(format!("[ejecutar_consulta] Error al preparar la consulta: {}", err)),
    };

    // Crear un solo vector de parámetros
    let params: Vec<Value> = meta_keys
        .iter()
        .map(|s| s.to_string().into())
        .chain(posts_ids.iter().map(|id| (*id).into()))
        .collect();

    // Ejecutar la consulta preparada
    let meta_resultados = stmt.exec_iter(params);

    let mut meta_data: HashMap<i64, HashMap<String, String>> = HashMap::new();

    match meta_resultados {
        Ok(mut result) => {
          let rows: Result<Vec<Row>, mysql::Error> = result.map(|row_result| row_result).collect();
            match rows {
                Ok(rows) => {
                    for row in rows.into_iter() {
                        let post_id: i64 = row.get("post_id").unwrap_or(0);
                        let meta_key: String = row.get("meta_key").unwrap_or_else(|| "".to_string());
                        let meta_value: String = row.get("meta_value").unwrap_or_else(|| "".to_string());

                        meta_data
                            .entry(post_id)
                            .or_insert_with(HashMap::new)
                            .insert(meta_key, meta_value);
                    }
                }
                Err(err) => {
                    return Err(format!("[ejecutar_consulta] Error en la consulta: {}", err));
                }
            }
        }
        Err(err) => {
            return Err(format!("[ejecutar_consulta] Error en la consulta: {}", err));
        }
    }

    Ok((meta_data, logs))
}

#[php_module]
pub fn module(module: ModuleBuilder) -> ModuleBuilder {
    module
}