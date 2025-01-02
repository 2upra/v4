use ext_php_rs::prelude::*;

#[php_function]
pub fn hola_mundo() -> String {
    "¡Hola Mundo desde Rust!".to_string()
}

#[php_module]
pub fn get_module(module: ModuleBuilder) -> ModuleBuilder {
    module.build()
}
