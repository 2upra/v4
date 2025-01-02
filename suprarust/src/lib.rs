use ext_php_rs::prelude::*;
use ext_php_rs::builders::ModuleBuilder;

/// Suma dos números enteros.
/// 
/// @param int $a Primer número.
/// @param int $b Segundo número.
/// 
/// @return int La suma de los dos números.
#[php_function]
pub fn calcular_suma(a: i32, b: i32) -> i32 {
    a + b
}

/// Multiplica dos números enteros.
/// 
/// @param int $a Primer número.
/// @param int $b Segundo número.
/// 
/// @return int El producto de los dos números.
#[php_function]
pub fn calcular_multiplicacion(a: i32, b: i32) -> i32 {
    a * b
}

/// Realiza operaciones combinadas con un número.
/// 
/// @param int $x Número base.
/// 
/// @return string Resultado formateado de las operaciones.
#[php_function]
pub fn operaciones_combinadas(x: i32) -> String {
    let suma = calcular_suma(x, 5);
    let multiplicacion = calcular_multiplicacion(suma, 2);
    format!(
        "El resultado de la suma es: {}, y el de la multiplicación es: {}",
        suma, multiplicacion
    )
}

/// Registro del módulo PHP.
#[php_module]
pub fn module(module: ModuleBuilder) -> ModuleBuilder {
    module
}
