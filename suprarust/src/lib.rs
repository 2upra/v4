#![cfg_attr(windows, feature(abi_vectorcall))]
use ext_php_rs::prelude::*;
use mysql::Pool;
use mysql::prelude::Queryable; // Importa el trait Queryable aquí
use std::env;
use dotenv::dotenv;

fn get_db_pool() -> Result<Pool, String> {
    dotenv().ok();

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
                    // Ahora puedes usar query_drop porque Queryable está en el ámbito
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

#[php_module]
pub fn module(module: ModuleBuilder) -> ModuleBuilder {
    module
}