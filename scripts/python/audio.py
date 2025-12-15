import essentia.standard as es
import json

def analizar_audio(audio_path):
    try:
        audio = es.MonoLoader(filename=audio_path)()
        print(f"Audio cargado correctamente. Longitud: {len(audio)} muestras.")

        # Extraer BPM
        rhythm_extractor = es.RhythmExtractor2013(method="multifeature")
        bpm, _, _, _, _ = rhythm_extractor(audio)
        bpm = round(bpm)  # Redondear el BPM al entero más cercano
        print(f"BPM detectado: {bpm}")

        # Extraer tono
        pitch_extractor = es.PredominantPitchMelodia()
        pitch, _ = pitch_extractor(audio)
        print(f"Pitch (tono) detectado: {pitch[:10]}")

        # Otros datos relevantes
        key_extractor = es.KeyExtractor()
        key, scale, strength = key_extractor(audio)
        print(f"Clave detectada: {key}, Escala: {scale}, Fuerza: {strength}")

        # Crear un diccionario con los resultados
        resultados = {
            "bpm": bpm,
            "pitch": pitch.tolist(),
            "key": key,
            "scale": scale,
            "strength": strength
        }

        # Guardar los resultados en un archivo JSON
        with open(audio_path + '_resultados.json', 'w') as f:
            json.dump(resultados, f)
        print("Archivo JSON guardado correctamente.")

        return resultados

    except Exception as e:
        print(f"Error durante el análisis del audio: {e}")

# Ejemplo de uso
if __name__ == "__main__":  
    import sys
    audio_path = sys.argv[1]
    resultados = analizar_audio(audio_path)
    print(resultados)
