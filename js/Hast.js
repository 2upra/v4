// Logs
const enableLogs = true;
const logHast = enableLogs ? console.log : function () {};

// Genera un hash SHA-256 del archivo
window.generateFileHash = async function(file) {
    logHast("Iniciando generación de hash para el archivo:", file.name);
    
    // Convertir el archivo a un ArrayBuffer
    const buffer = await file.arrayBuffer();
    logHast("ArrayBuffer generado:", buffer);
    
    // Generar hash con SHA-256
    const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
    logHast("Hash buffer generado (SHA-256):", hashBuffer);
    
    // Convertir el buffer a una matriz de bytes y luego a un string hexadecimal
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    logHast("Array convertido a Uint8Array:", hashArray);
    
    // Convertir el array de bytes en un string hexadecimal
    const hash = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    logHast("Hash final generado:", hash);
    
    return hash;
};
