use ext_php_rs::prelude::*;
use ext_php_rs::builders::ModuleBuilder;


#[php_function]
pub fn calcular_suma(a: i32, b: i32) -> i32 {
    a + b
}


#[php_function]
pub fn calcular_multiplicacion(a: i32, b: i32) -> i32 {
    a * b
}

#[php_function]
pub fn operaciones_combinadas(x: i32) -> String {
    let suma = calcular_suma(x, 5);
    let multiplicacion = calcular_multiplicacion(suma, 2);
    format!(
        "El resultado de la suma es: {}, y el de la multiplicación es: {}",
        suma, multiplicacion
    )
}

#[php_module]
pub fn module(module: ModuleBuilder) -> ModuleBuilder {
    module
}
