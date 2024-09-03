<?php
/*
Template Name: Inicio Prueba
*/
get_header();


?>



<div id="main">
	<div id="content">

		<style>
			nav#menu1 {
				display: none;
			}
		</style>
		<div class="XX1 A3005241226 C2024715">

			<p class="XXT1">Progreso y evolución de 2upra</p>


			<?php if (!is_user_logged_in()) : ?>
				<div class="XX12">
					<button class="XXB1 boton-registro" id="botonregistro">Regístrate</button>
					<button class="XXB1 XXB3 boton-sesion" id="botonsesion">Iniciar Sesión</button>
				</div>

				<div class="XX11" id="modalregistro">
					<?php echo do_shortcode('[registrar_usuario]'); ?>
				</div>
				<div class="XX11 XX13" id="modalsesion">
					<?php echo do_shortcode('[iniciar_sesion]'); ?>
				</div>
			<?php endif; ?>

			<div id="fondonegro"></div>

		</div>


		<div class="XX1 A300524152 C2024715">

			<div class="A300524153">
				<div class="subtituloprogreso"></div>
				
				<div id="contenidolista" class="acciones">
					<?php echo do_shortcode('[mostrar_informacion todos="true"]'); ?>
				</div>
				<div id="contenidoperfil" style="display:none;">
					<?php

					if (is_user_logged_in()) :
						$user = wp_get_current_user();
						$profile_picture = obtener_url_imagen_perfil_o_defecto($user->ID);
						$acc = get_user_meta($user->ID, 'acciones', true);
						$totalAcciones = 800000;
						$res = calc_ing(48, false);
						$valAcc = $res['valAcc'];
						$valD = $acc * $valAcc;

						// Formatear la fecha de registro
						$fecha_registro = date('d/m/Y', strtotime($user->user_registered));
					?>
						<img src="<?php echo esc_url($profile_picture); ?>" alt="Profile Picture">
						<p><?php echo esc_html($user->user_login); ?></p>
						<p><?php echo esc_html($fecha_registro); ?></p>
						<p>Acciones: <?php echo esc_html($acc); ?></p>
						<p>Valor: $<?php echo number_format($valD, 2, '.', '.'); ?></p>
						<button id="reportarerror" class="reportarerror">Reportar un error</button>
					<?php else : ?>
						<p>Inicia sesión para ver tus acciones</p>

						<div class="XX12">
							<button class="XXB1 XXB3 boton-sesion" id="botonsesion">Iniciar Sesión</button>
						</div>

					<?php endif; ?>

				</div>
				<div id="contenidocomprar" style="display:none;">
					<?php
					// Mostrar formulario SOLO si el usuario está logeado 
					if (is_user_logged_in()) :
					?>
						<input type="text" id="cantidadCompra" placeholder="$">
						<input type="hidden" id="cantidadReal">
						<input type="hidden" id="userID" value="<?php echo get_current_user_id(); ?>">
						<p>Invertir en 2upra comprando acciones, ingrese la cantidad que desea invertir</p>
						<div class="a120724801">
							<button id="botonComprar">Comprar</button>
							<button id="SuscribirseAcciones">Suscribirse</button>
						</div>
					<?php else : ?>
						<p>Inicia sesión para comprar acciones</p>
						<div class="XX12">
							<button class="XXB1 XXB3 boton-sesion" id="botonsesion">Iniciar Sesión</button>
						</div>

					<?php endif; ?>
					<!-- <button id="pagosalternativos">Pagos alternativos</button> -->
				</div>

			</div>

			<div class="GraficoCapital" id="miGraficoCapital">
				<div class="datos2upra"><?php echo do_shortcode('[unified_shortcode]'); ?></div>
				<?php echo do_shortcode('[capitalvalores]'); ?>
			</div>
		</div>

		<div class="XX1 A1607241136 C2024715" id="contenedor1707">


			<div class="X170724214 XX7 PROGRESO E17072412" id="ppp4">
				<div class="XXDD XX9">
					<div class="XX10">
						<h3 class="XXD11" id="startDate">05/01/2024</h3>
						<h3 class="XXD1">4. Rehacer y pulir </h3>
						<p class="XXD2">En esta etapa, muchas funcionalidades han sido refinadas, incluyendo el rediseño
							de la interfaz, mejoras en el rendimiento y una modernización significativa de las
							interfaces para artistas y seguidores. Lo más destacable de este periodo es la creación de
							funciones más claras y comprensibles para cada tipo de usuario.</p>

						<h3 class="XXD1 230624810">En progreso</h3>

						<div id="avancesContent" class="avances-content avancesContent">
							<ul>
								<li>+ Se han rediseñado las interfaces para mejorar la experiencia del usuario.</li>
								<li>+ Se ha realizado una separación de funcionalidades específicas para artistas y
									seguidores. </li>
								<li>+ Las interfaces ahora permiten un mejor entendimiento del propósito general de la
									plataforma.</li>
								<li>+ Se ha implementado un sistema óptimo para filtrar y encontrar recursos dirigidos a
									los artistas / mejora en como se muestra el contenido para seguidores. </li>
								<li>+ Se espera pulir las funcionalidades de interacción como las reacciones, chat,
									subida contenido y la gestión de este mismo, así como seguir facilitando el
									entendimiento para los proximos usuarios nuevos.</li>
							</ul>
						</div>

					</div>
				</div>
			</div>

			<div class="X170724214 XX7 PROGRESO E17072412" id="ppp3">
				<div class="XXDD XX9">
					<div class="XX10">
						<h3 class="XXD11" id="startDate">04/01/2024</h3>
						<h3 class="XXD1">3. Complejidad</h3>
						<p class="XXD2">En este punto, se comprende que realizar un trabajo de alta calidad requiere una
							gran inversión de tiempo, esfuerzo y dedicación. Desarrollar la base para un chat en tiempo
							real desde cero es un logro significativo, especialmente considerando que es el primer
							acercamiento a la programación. Aunque hacerlo desde la base es complejo, es necesario
							debido a la naturaleza del resultado final que se desea alcanzar.</p>

						<h3 class="XXD1 230624810">Avances principales</h3>
						<div id="avancesContent" class="avances-content avancesContent">
							<ul>
								<li>+ Se ha implementado un chat en tiempo real para los usuarios.</li>
								<li>+ Se han implementado algoritmos básicos para mejorar la visualización del contenido
									para los usuarios.</li>
								<li>+ Se ha desarrollado un sistema justo para la descarga de contenido y la motivación
									para publicar (monedas).</li>
								<li>+ Se han realizado mejoras considerables en el rendimiento y el tiempo de carga,
									incluyendo un sistema de caché para la música que permite cargar cada pista solo una
									vez.</li>
							</ul>
						</div>
					</div>
				</div>
			</div>

			<div class="X170724214 XX7 PROGRESO E17072412" id="ppp2">
				<div class="XXDD XX9">
					<div class="XX10">
						<h3 class="XXD11" id="startDate">03/01/2024</h3>
						<h3 class="XXD1">2. Prueba y errores</h3>
						<p class="XXD2">Se han desarrollado diversas funciones desde cero. Debido a la complejidad y los
							requisitos del proyecto, todas las funcionalidades se están programando desde la base. A
							pesar de que muchas de las funcionalidades complejas ya operan correctamente, aún requieren
							mejoras en cuanto a calidad, experiencia visual y otros aspectos para alcanzar el nivel
							deseado.</p>

						<h3 class="XXD1 230624810">Avances principales</h3>

						<div id="avancesContent" class="avances-content avancesContent">
							<ul>
								<li>+ Se han implementado exitosamente todas las funcionalidades de interactividad,
									tales como "Me gusta", comentarios, notificaciones, la opción de seguir a otros
									usuarios, etc.</li>
								<li>+ Se han desarrollado las funcionalidades necesarias para la monetización de
									contenido, incluyendo la publicación de beats para la venta, suscripciones y
									compras.</li>
								<li>+ Se ha estructurado un modelo similar al de Spotify para la reproducción de música.
								</li>
								<li>+ La carga dinámica de páginas y todo tipo de contenido se realiza de manera
									eficiente.</li>
							</ul>
						</div>
					</div>
				</div>
			</div>

			<div class="X170724214 XX7 PROGRESO E17072412" id="ppp1">
				<div class="XXDD XX9">
					<div class="XX10">
						<h3 class="XXD11" id="startDate">01/01/2024</h3>
						<h3 class="XXD1">1. Comienzo</h3>
						<p class="XXD2">El planteamiento es claro: desarrollar una plataforma con funcionalidades
							innovadoras, que incluya un conjunto de herramientas para artistas y un espacio dedicado
							para sus seguidores. Inicialmente, se estimó que la realización de este proyecto no tomaría
							más de dos meses; sin embargo, la complejidad del mismo fue subestimada desde el principio.
						</p>

						<h3 class="XXD1 230624810">Avances principales</h3>

						<div id="avancesContent" class="avances-content avancesContent">
							<ul>
								<li>+ Se plantea las funcionalidades principales.</li>
								<li>+ Se consigue inversiones necesarias y recurrentes para el proyecto.</li>
								<li>+ Se comienza a escribir las primeras lineas de codigo.</li>
							</ul>
						</div>
						<p id="timeAgo"></p>
					</div>
				</div>
			</div>
			<p class="textopeq">Ultimos progresos publicados</p>
			<div id="barraProgreso1707"></div>
		</div>


	</div>
</div>


<?php
get_footer();
//ENCOLADORES EN PANEL.PHP
?>