#![cfg_attr(windows, feature(abi_vectorcall))]
use ext_php_rs::prelude::*;
use mysql_async::prelude::*;
use mysql_async::{Pool, Error as MySqlError};
use lazy_static::lazy_static;
use std::env;
use dotenv::dotenv;
use tokio::runtime::Runtime;
use std::sync::Arc;

lazy_static! {
    static ref MYSQL_POOL: Arc<Pool> = {
        dotenv().ok();

        let db_user = env::var("DB_USER").expect("Variable DB_USER no encontrada en .env");
        let db_password = env::var("DB_PASSWORD").expect("Variable DB_PASSWORD no encontrada en .env");
        let db_host = env::var("DB_HOST").expect("Variable DB_HOST no encontrada en .env");
        let db_name = env::var("DB_NAME").expect("Variable DB_NAME no encontrada en .env");
        let db_port = env::var("DB_PORT").unwrap_or_else(|_| "3306".to_string());

        let url = format!(
            "mysql://{}:{}@{}:{}/{}",
            db_user, db_password, db_host, db_port, db_name
        );

        Arc::new(Pool::new(url.as_str()))
    };
}

#[php_function]
pub fn conectar_bd() -> String {
    let pool = MYSQL_POOL.clone();
    let mut log = String::new();

    let rt = Runtime::new().unwrap();
    let result = rt.block_on(async {
        match pool.get_conn().await {
            Ok(_) => "Conexión exitosa a la base de datos.".to_string(),
            Err(err) => format!("Error al conectar a la base de datos: {}", err),
        }
    });

    log.push_str(&result);
    log
}

#[php_module]
pub fn module(module: ModuleBuilder) -> ModuleBuilder {
    module
}


/* #![cfg_attr(windows, feature(abi_vectorcall))]
use ext_php_rs::prelude::*;
use ext_php_rs::types::Zval;
use mysql_async::prelude::*;
use mysql_async::{Pool, Row, Error as MySqlError};
use std::collections::HashMap;
use lazy_static::lazy_static;
use std::env;
use dotenv::dotenv;
use tokio::runtime::Runtime;
use std::sync::Arc;
use ext_php_rs::convert::IntoZval;
use ext_php_rs::zend::ExecuteData;

lazy_static! {
    static ref MYSQL_POOL: Arc<Pool> = {
        dotenv().ok();

        let db_user = env::var("DB_USER").expect("Variable DB_USER no encontrada en .env");
        let db_password = env::var("DB_PASSWORD").expect("Variable DB_PASSWORD no encontrada en .env");
        let db_host = env::var("DB_HOST").expect("Variable DB_HOST no encontrada en .env");
        let db_name = env::var("DB_NAME").expect("Variable DB_NAME no encontrada en .env");
        let db_port = env::var("DB_PORT").unwrap_or_else(|_| "3306".to_string());

        let url = format!(
            "mysql://{}:{}@{}:{}/{}",
            db_user, db_password, db_host, db_port, db_name
        );

        Arc::new(Pool::new(url.as_str()))
    };
}

#[php_function]
pub fn obtener_metadatos_posts_rust(posts_ids: Vec<i64>) -> Result<Zval, String> {
    let pool_clone = MYSQL_POOL.clone();
    let meta_keys = vec!["datosAlgoritmo", "Verificado", "postAut", "artista", "fan"];

    let rt = Runtime::new().unwrap();

    let result = rt.block_on(async {
        ejecutar_consulta(pool_clone, posts_ids, meta_keys).await
    });

    match result {
        Ok((meta_data, logs)) => {
            let mut result_map = HashMap::new();

            let mut meta_data_map = HashMap::new();
            for (post_id, meta_map) in meta_data {
                let mut post_meta_map = HashMap::new();
                for (key, value) in meta_map {
                    post_meta_map.insert(key, value.into_zval(false).unwrap());
                }
                meta_data_map.insert(post_id.to_string(), post_meta_map.into_zval(false).unwrap());
            }
            result_map.insert("meta_data".to_string(), meta_data_map.into_zval(false).unwrap());

            result_map.insert("logs".to_string(), logs.into_zval(false).unwrap());

            Ok(result_map.into_zval(false).unwrap())
        },
        Err(err) => Err(err),
    }
}

async fn ejecutar_consulta(pool_clone: Arc<Pool>, posts_ids: Vec<i64>, meta_keys: Vec<&str>) -> Result<(HashMap<i64, HashMap<String, String>>, Vec<String>), String> {
    let mut logs = Vec::new();
    let conn_result = pool_clone.get_conn().await;

    let mut conn = match conn_result {
        Ok(conn) => conn,
        Err(err) => {
            logs.push(format!("[ejecutar_consulta] Error al obtener la conexión: {}", err));
            return Err(format!("[ejecutar_consulta] Error al obtener la conexión: {}", err));
        }
    };

    let placeholders = posts_ids.iter().map(|_| "?").collect::<Vec<_>>().join(", ");
    let meta_keys_placeholders = meta_keys.iter().map(|_| "?").collect::<Vec<_>>().join(", ");

    let sql_meta = format!(
        "SELECT post_id, meta_key, meta_value
         FROM wp_postmeta
         WHERE meta_key IN ({}) AND post_id IN ({})",
        meta_keys_placeholders, placeholders
    );

    let params_vec: Vec<String> = meta_keys
        .iter()
        .map(|s| s.to_string())
        .chain(posts_ids.iter().map(|id| id.to_string()))
        .collect();

    let params: Vec<&str> = params_vec.iter().map(|s| s.as_str()).collect();

    let meta_resultados: Result<Vec<Row>, MySqlError> = conn.exec(sql_meta, params).await;

    let mut meta_data: HashMap<i64, HashMap<String, String>> = HashMap::new();

    match meta_resultados {
        Ok(rows) => {
            for row in rows {
                let post_id: i64 = match row.get("post_id") {
                    Some(v) => v,
                    None => {
                        logs.push("[ejecutar_consulta] Fila sin post_id".to_string());
                        continue;
                    }
                };
                let meta_key: String = match row.get("meta_key") {
                    Some(v) => v,
                    None => {
                        logs.push("[ejecutar_consulta] Fila sin meta_key".to_string());
                        continue;
                    }
                };
                let meta_value: String = match row.get("meta_value") {
                    Some(v) => v,
                    None => {
                        logs.push("[ejecutar_consulta] Fila sin meta_value".to_string());
                        continue;
                    }
                };

                meta_data
                    .entry(post_id)
                    .or_insert_with(HashMap::new)
                    .insert(meta_key, meta_value);
            }
        },
        Err(err) => {
            logs.push(format!("[ejecutar_consulta] Error en la consulta: {}", err));
            return Err(format!("[ejecutar_consulta] Error en la consulta: {}", err));
        }
    }

    Ok((meta_data, logs))
}

#[php_module]
pub fn module(module: ModuleBuilder) -> ModuleBuilder {
    module
}

    */
