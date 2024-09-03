window.uploadFile = async function (file, progressBarId, formNumber) {
    const formData = new FormData();
    formData.append('action', 'file_upload');
    formData.append('file', file);

    const fileHash = await generateFileHash(file);
    formData.append('file_hash', fileHash);

    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', my_ajax_object.ajax_url, true);

        xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                const progressBar = document.getElementById(progressBarId);
                if (progressBar) {
                    progressBar.style.width = percentComplete + '%';
                }
            }
        };

        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.success) {
                        // Diferenciar entre audio y otros tipos de archivo
                        const fileType = file.type.split('/')[0]; // Obtener el tipo principal del archivo (e.g., "audio", "image", etc.)
                        if (fileType === 'audio') {
                            console.log('Audio subido:', result.data.fileUrl);
                            window.formState.uploadedFiles[formNumber] = true;
                            window.formState.uploadedFileUrls[formNumber] = result.data.fileUrl;
                        } else {
                            console.log('Archivo subido (' + file.type + '):', result.data.fileUrl);
                            window.formState.archivo[formNumber] = true;
                        }
        
                        console.log('Estado actual de uploadedFiles:', window.formState.uploadedFiles);
                        checkAllFilesUploaded();
                        window.ChequearFormRola();
                        resolve(result.data.fileUrl);
                    } else {
                        reject(new Error(result.data));
                    }
                } catch (error) {
                    reject(error);
                }
            } else {
                reject(new Error('Error en la carga del archivo'));
            }
        };
        xhr.onerror = function () {
            reject(new Error('Error en la carga del archivo'));
        };
        xhr.send(formData);
    });
};


async function generateFileHash(file) {
    console.log("Iniciando generación de hash para el archivo:", file.name);

    const buffer = await file.arrayBuffer();
    console.log("ArrayBuffer generado:", buffer);

    const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
    console.log("Hash buffer generado:", hashBuffer);

    const hashArray = Array.from(new Uint8Array(hashBuffer));
    console.log("Array convertido a Uint8Array:", hashArray);

    const hash = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    console.log("Hash final generado:", hash);

    return hash;
}


function checkAllFilesUploaded() {
    const allUploaded = Object.values(window.formState.uploadedFiles).every(Boolean);
    console.log('Estado inicial de uploadedFiles:', window.formState.uploadedFiles);
    console.log('Estado inicial de uploadedFileUrls:', window.formState.uploadedFileUrls); // Log de uploadedFileUrls inicial

    if (allUploaded) {
        window.formState.cargaCompleta = true;
        console.log('Todos los archivos han sido cargados');
    }

    console.log('Estado final de uploadedFiles:', window.formState.uploadedFiles); // Log del estado final de uploadedFiles
    console.log('Estado final de uploadedFileUrls:', window.formState.uploadedFileUrls); // Log del estado final de uploadedFileUrls
}
