# hashAudio.py
import sys
import hashlib
import numpy as np
import librosa

def calcular_hash_audio(audio_path):
    try:
        # Cargar el audio
        y, sr = librosa.load(audio_path, sr=None)
        
        # Calcular características que son más resistentes a cambios de formato
        # Mel-frequency cepstral coefficients (MFCCs)
        mfcc = librosa.feature.mfcc(y=y, sr=sr, n_mfcc=20)
        
        # Chromagram
        chroma = librosa.feature.chroma_stft(y=y, sr=sr)
        
        # Spectral Centroid
        spectral_centroid = librosa.feature.spectral_centroid(y=y, sr=sr)
        
        # Combinar características y redondear para mayor estabilidad
        features = np.concatenate([
            np.mean(mfcc, axis=1),
            np.mean(chroma, axis=1),
            np.mean(spectral_centroid, axis=1)
        ])
        
        # Redondear a 6 decimales para mayor estabilidad
        features = np.round(features, 6)
        
        # Convertir a bytes y crear hash
        features_bytes = features.tobytes()
        hash_obj = hashlib.sha256(features_bytes)
        
        return hash_obj.hexdigest()
            
    except Exception as e:
        sys.stderr.write(f"Error procesando archivo: {str(e)}\n")
        return None

if __name__ == "__main__":
    if len(sys.argv) != 2:
        sys.stderr.write("Error: Se requiere la ruta del archivo de audio\n")
        sys.exit(1)
    
    audio_path = sys.argv[1]
    hash_result = calcular_hash_audio(audio_path)
    
    if hash_result:
        print(hash_result)
    else:
        sys.exit(1)