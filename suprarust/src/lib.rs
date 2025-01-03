#![cfg_attr(windows, feature(abi_vectorcall))]
use ext_php_rs::prelude::*;
use mysql::Pool; // Usamos la biblioteca síncrona mysql
use lazy_static::lazy_static;
use std::env;
use dotenv::dotenv;

lazy_static! {
    static ref MYSQL_POOL: Pool = {
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

        Pool::new(url.as_str()).expect("Error al crear el pool de conexiones")
        // Se agregó un `expect` aquí para manejar un posible error en la creación del pool
    };
}

#[php_function]
pub fn conectar_bd() -> String {
    let mut log = String::new();

    match MYSQL_POOL.get_conn() {
        Ok(_) => log.push_str("Conexión exitosa a la base de datos."),
        Err(err) => log.push_str(&format!("Error al conectar a la base de datos: {}", err)),
    }

    log
}

#[php_module]
pub fn module(module: ModuleBuilder) -> ModuleBuilder {
    module
}