import os
import librosa
import numpy as np
import hashlib

print("Inicio del script")  # Confirmación de que el script comienza

def calcular_hash_audio(audio_path):
    try:
        print(f"Procesando archivo: {audio_path}")  # Depuración de ruta de archivo
        
        # Verificar si el archivo existe
        if not os.path.exists(audio_path):
            print(f"Archivo no encontrado: {audio_path}")
            return None

        # Intentar cargar el archivo
        try:
            y, sr = librosa.load(audio_path, sr=None)
            print("Archivo cargado exitosamente")  # Confirmación de carga
        except Exception as load_error:
            print(f"Error cargando el archivo de audio: {audio_path}, {load_error}")
            return None

        # Confirmación de inicio del procesamiento de espectrograma
        print("Iniciando cálculo de espectrograma")
        mel_spectrogram = librosa.feature.melspectrogram(y=y, sr=sr)
        print("Espectrograma calculado")  # Confirmación de espectrograma
        
        log_mel_spectrogram = librosa.power_to_db(mel_spectrogram, ref=np.max)
        print("Espectrograma logarítmico calculado")  # Confirmación de espectrograma logarítmico
        
        mel_bytes = log_mel_spectrogram.tobytes()
        print("Espectrograma convertido a bytes")  # Confirmación de conversión a bytes
        
        hash_obj = hashlib.sha256(mel_bytes)
        print("Hash generado exitosamente")  # Confirmación de hash
        
        return hash_obj.hexdigest()

    except Exception as e:
        print(f"Error en el procesamiento de hash para el archivo {audio_path}: {e}")
        return None

# Llamada a la función para procesar el archivo
print(calcular_hash_audio('/var/www/wordpress/wp-content/uploads/2024/10/Drum-Loop-15_uQyO_2upra.wav'))
