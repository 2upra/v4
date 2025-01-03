#![cfg_attr(windows, feature(abi_vectorcall))]
use ext_php_rs::prelude::*;
use mysql::Pool;
use mysql::prelude::Queryable;
use std::env;
use std::path::Path;
use ext_php_rs::types::Zval;
use std::collections::HashMap;
use mysql::*;
use ext_php_rs::convert::IntoZval;

fn get_db_pool() -> Result<Pool, String> {
    let project_dir = "/var/www/wordpress/wp-content/themes/2upra3v/suprarust";
    let env_path = Path::new(project_dir).join(".env");
    dotenv::from_path(&env_path).map_err(|e| format!("Error al cargar el archivo .env: {}", e))?;

    let db_user = env::var("DB_USER").map_err(|_| "Variable DB_USER no encontrada en .env".to_string())?;
    let db_password = env::var("DB_PASSWORD").map_err(|_| "Variable DB_PASSWORD no encontrada en .env".to_string())?;
    let db_host = env::var("DB_HOST").map_err(|_| "Variable DB_HOST no encontrada en .env".to_string())?;
    let db_name = env::var("DB_NAME").map_err(|_| "Variable DB_NAME no encontrada en .env".to_string())?;
    let db_port = env::var("DB_PORT").unwrap_or_else(|_| "3306".to_string());

    let url = format!(
        "mysql://{}:{}@{}:{}/{}",
        db_user, db_password, db_host, db_port, db_name
    );

    Pool::new(url.as_str()).map_err(|e| format!("Error al crear el pool de conexiones: {}", e))
}

#[php_function]
pub fn conectar_bd_sin_lazy_static() -> String {
    match get_db_pool() {
        Ok(pool) => {
            match pool.get_conn() {
                Ok(mut conn) => {
                    match conn.query_drop("SELECT 1") {
                        Ok(_) => "Conexión exitosa a la base de datos.".to_string(),
                        Err(err) => format!("Error al ejecutar consulta de prueba: {}", err),
                    }
                }
                Err(err) => format!("Error al obtener conexión del pool: {}", err),
            }
        }
        Err(err) => err,
    }
}

#[php_function]
pub fn solo_conectar_bd() -> String {
    match get_db_pool() {
        Ok(_) => "Pool de conexiones creado con éxito.".to_string(),
        Err(err) => err,
    }
}

#[php_function]
pub fn obtener_metadatos_posts_rust(posts_ids: Vec<i64>) -> Result<Zval, String> {
    let pool = match get_db_pool() {
        Ok(pool) => pool,
        Err(err) => return Err(err),
    };

    let meta_keys = vec!["datosAlgoritmo", "Verificado", "postAut", "artista", "fan"];
    let (meta_data, logs) = match ejecutar_consulta(pool, posts_ids, meta_keys) {
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
    res_map.insert("meta_data".to_string(), meta_data_map.into_zval(false).unwrap());
    res_map.insert("logs".to_string(), logs.into_zval(false).unwrap());

    Ok(res_map.into_zval(false).unwrap())
}

fn ejecutar_consulta(
    pool: Pool,
    posts_ids: Vec<i64>,
    meta_keys: Vec<&str>,
) -> Result<(HashMap<i64, HashMap<String, String>>, Vec<String>), String> {
    let mut logs = Vec::new();
    let mut conn = match pool.get_conn() {
        Ok(conn) => conn,
        Err(err) => {
            // logs.push(format!("[ejecutar_consulta] Error al obtener la conexión: {}", err));
            return Err(format!(
                "[ejecutar_consulta] Error al obtener la conexión: {}",
                err
            ));
        }
    };

    // logs.push("[ejecutar_consulta] Conexión a la base de datos establecida.".to_string());

    let placeholders = posts_ids.iter().map(|_| "?").collect::<Vec<_>>().join(", ");
    // logs.push(format!("[ejecutar_consulta] Placeholders para post_ids: {}", placeholders));

    let meta_keys_placeholders = meta_keys.iter().map(|_| "?").collect::<Vec<_>>().join(", ");
    // logs.push(format!(
    //     "[ejecutar_consulta] Placeholders para meta_keys: {}",
    //     meta_keys_placeholders
    // ));

    let sql_meta = format!(
        "SELECT post_id, meta_key, meta_value
         FROM wpsg_postmeta
         WHERE meta_key IN ({}) AND post_id IN ({})",
        meta_keys_placeholders, placeholders
    );
    // logs.push(format!("[ejecutar_consulta] Consulta SQL: {}", sql_meta));

    let params_vec: Vec<String> = meta_keys
        .iter()
        .map(|s| s.to_string())
        .chain(posts_ids.iter().map(|id| id.to_string()))
        .collect();
    // logs.push(format!("[ejecutar_consulta] Parámetros (como strings): {:?}", params_vec));

    let params: Vec<&str> = params_vec.iter().map(|s| s.as_str()).collect();
    // logs.push(format!("[ejecutar_consulta] Parámetros (como &str): {:?}", params));

    let meta_resultados: Result<Vec<Row>, mysql::Error> =
        conn.exec(sql_meta, params);

    let mut meta_data: HashMap<i64, HashMap<String, String>> = HashMap::new();

    match meta_resultados {
        Ok(rows) => {
            // logs.push(format!("[ejecutar_consulta] Consulta ejecutada con éxito. Filas obtenidas: {}", rows.len()));
            for (i, row) in rows.into_iter().enumerate() {
                // logs.push(format!("[ejecutar_consulta] Procesando fila {}: {:?}", i, row));
                let post_id: i64 = match row.get("post_id") {
                    Some(v) => v,
                    None => {
                        // logs.push(format!("[ejecutar_consulta] Fila {} sin post_id", i));
                        continue;
                    }
                };
                let meta_key: String = match row.get("meta_key") {
                    Some(v) => v,
                    None => {
                        // logs.push(format!("[ejecutar_consulta] Fila {} sin meta_key", i));
                        continue;
                    }
                };
                let meta_value: String = match row.get("meta_value") {
                    Some(v) => v,
                    None => {
                        // logs.push(format!("[ejecutar_consulta] Fila {} sin meta_value", i));
                        continue;
                    }
                };

                // logs.push(format!(
                //     "[ejecutar_consulta] Fila {}: post_id={}, meta_key={}, meta_value={}",
                //     i, post_id, meta_key, meta_value
                // ));

                meta_data
                    .entry(post_id)
                    .or_insert_with(HashMap::new)
                    .insert(meta_key, meta_value);
            }
        },
        Err(err) => {
            // logs.push(format!("[ejecutar_consulta] Error en la consulta: {}", err));
            return Err(format!("[ejecutar_consulta] Error en la consulta: {}", err));
        }
    }
    // logs.push("[ejecutar_consulta] Fin de la función ejecutar_consulta".to_string());
    Ok((meta_data, logs))
}

#[php_module]
pub fn module(module: ModuleBuilder) -> ModuleBuilder {
    module
}