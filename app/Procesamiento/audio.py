# import essentia.standard as es
# import json
# import numpy as np 

# def analizar_audio(audio_path):
#     try:
#         # Cargar el audio a 16kHz como requiere el modelo
#         audio = es.MonoLoader(filename=audio_path, sampleRate=16000)()
#         print(f"Audio cargado correctamente. Longitud: {len(audio)} muestras.")
        
#         # Extraer embeddings usando TensorflowPredictEffnetDiscogs
#         effnet_discogs = es.TensorflowPredictEffnetDiscogs(
#             graphFilename='/var/www/wordpress/wp-content/themes/2upra3v/app/Procesamiento/discogs-effnet-bs64-1.pb',
#             output='PartitionedCall:1',  # Asegúrate de que el nombre del tensor es correcto
#             patchHopSize=62,
#             patchSize=128,
#             batchSize=1,  # Usar batch size de 1 para optimizar memoria
#             lastBatchMode='same'
#         )
        
#         embeddings = effnet_discogs(audio)
#         print(f"Embeddings extraídos: {embeddings.shape}")

#         # Usar embeddings para predecir el género musical
#         genre_model = es.TensorflowPredict2D(
#             graphFilename='/var/www/wordpress/wp-content/themes/2upra3v/app/Procesamiento/genre_discogs400-discogs-effnet-1.pb',
#             input="serving_default_model_Placeholder",  # Asegúrate de que el nombre del tensor es correcto
#             output="PartitionedCall:0"
#         )
        
#         predictions = genre_model(embeddings)
#         print(f"Predicciones de género: {predictions[:5]}")  # Mostrar las primeras 5 predicciones

#         # Extraer BPM (mantenemos el método original por ahora)
#         rhythm_extractor = es.RhythmExtractor2013(method="multifeature")
#         bpm, _, _, _, _ = rhythm_extractor(audio)
#         print(f"BPM detectado: {bpm}")

#         # Extraer tono (mantenemos el método original por ahora)
#         pitch_extractor = es.PredominantPitchMelodia()
#         pitch, _ = pitch_extractor(audio)
#         print(f"Pitch (tono) detectado: {pitch[:10]}")

#         # Otros datos relevantes (mantenemos el método original por ahora)
#         key_extractor = es.KeyExtractor()
#         key, scale, strength = key_extractor(audio)
#         print(f"Clave detectada: {key}, Escala: {scale}, Fuerza: {strength}")

#         # Crear un diccionario con los resultados
#         resultados = {
#             "bpm": bpm,
#             "pitch": pitch.tolist(),
#             "key": key,
#             "scale": scale,
#             "strength": strength,
#             "genre_predictions": predictions.tolist()
#         }

#         # Guardar los resultados en un archivo JSON
#         with open(audio_path + '_resultados.json', 'w') as f:
#             json.dump(resultados, f)
#         print("Archivo JSON guardado correctamente.")

#         return resultados

#     except Exception as e:
#         print(f"Error durante el análisis del audio: {e}")

# # Ejemplo de uso
# if __name__ == "__main__":
#     import sys
#     audio_path = sys.argv[1]
#     resultados = analizar_audio(audio_path)
#     print(resultados)


import essentia.standard as es
import json

def analizar_audio(audio_path):
    try:
        # Cargar el audio a 16kHz
        audio = es.MonoLoader(filename=audio_path, sampleRate=16000)()
        print(f"Audio cargado correctamente. Longitud: {len(audio)} muestras.")

        # Extraer embeddings usando TensorflowPredictEffnetDiscogs
        embedding_model = es.TensorflowPredictEffnetDiscogs(
            graphFilename="/var/www/wordpress/wp-content/themes/2upra3v/app/Procesamiento/discogs-effnet-bs64-1.pb",
            output="PartitionedCall:1",  # Ajustar según sea necesario
            patchHopSize=62,
            patchSize=128,
            batchSize=1,
            lastBatchMode='same'
        )
        
        embeddings = embedding_model(audio)
        print(f"Embeddings extraídos: {embeddings.shape}")

        # Usar embeddings para predecir el género musical
        genre_model = es.TensorflowPredict2D(
            graphFilename="/var/www/wordpress/wp-content/themes/2upra3v/app/Procesamiento/genre_discogs400-discogs-effnet-1.pb",  # Modelo de clasificación
            input="serving_default_model_Placeholder",  # Ajustar según sea necesario
            output="PartitionedCall:0"  # Ajustar según sea necesario
        )
        
        predictions = genre_model(embeddings)
        print(f"Predicciones de género: {predictions[:5]}")  # Mostrar las primeras 5 predicciones

        # Guardar los resultados en un archivo JSON
        resultados = {
            "embeddings_shape": embeddings.shape,
            "genre_predictions": predictions.tolist()
        }
        
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