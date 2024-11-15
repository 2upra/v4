<?
/*
Template Name: T&Q
*/

get_header();
$user_id = get_current_user_id();
$nologin_class = !is_user_logged_in() ? ' nologin' : '';
?>

<div id="main">
    <div id="content" class="<? echo esc_attr($nologin_class); ?>">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<? echo esc_attr(get_the_title()); ?>">
        
        <div class="RWOVSU">
            <h1>Términos y Condiciones de 2upra</h1>
            <p>Última actualización: 13 de octubre de 2024</p>

            <p>Bienvenido a 2upra.com ("2upra," "nosotros," "nuestro," o "la Plataforma"). Estos Términos y Condiciones ("Términos") rigen el acceso y uso de nuestro sitio web, aplicaciones, software, contenido, productos y servicios (colectivamente, los "Servicios"). Al acceder o utilizar los Servicios, usted ("Usuario", "usted" o "su") acepta estar legalmente obligado por estos Términos. Si no está de acuerdo con estos Términos, no acceda ni utilice los Servicios.</p>

            <ol class="ZPFSXX">
                <li>
                    <h2>Elegibilidad:</h2>
                    <p>Debe tener al menos 13 años de edad para utilizar los Servicios. Si es menor de 18 años, debe tener el consentimiento de sus padres o tutores para utilizar los Servicios y aceptar estos Términos en su nombre. Al utilizar los Servicios, declara y garantiza que tiene la edad legal suficiente para celebrar un contrato vinculante con nosotros.</p>
                </li>

                <li>
                    <h2>Cuenta de Usuario:</h2>
                    <p>Para acceder a ciertas funciones de los Servicios, debe crear una cuenta. Al crear una cuenta, usted acepta proporcionar información precisa, completa y actualizada. Usted es responsable de mantener la confidencialidad de su contraseña y de todas las actividades que ocurran en su cuenta. Nos notificará inmediatamente cualquier uso no autorizado de su cuenta o cualquier otra violación de seguridad. No somos responsables de ninguna pérdida o daño que resulte del incumplimiento de esta obligación.</p>
                </li>

                <li>
                    <h2>Uso Aceptable:</h2>
                    <p>Al utilizar los Servicios, usted acepta:</p>
                    <ul>
                        <li>No utilizar los Servicios para ningún propósito ilegal o no autorizado.</li>
                        <li>No violar ninguna ley, reglamento o norma local, estatal, nacional o internacional aplicable.</li>
                        <li>No infringir los derechos de propiedad intelectual de terceros, incluidos los derechos de autor, marcas comerciales y patentes.</li>
                        <li>No subir, publicar, transmitir o distribuir ningún contenido que sea ilegal, dañino, difamatorio, obsceno, acosador, amenazante, abusivo, odioso, racial o étnicamente ofensivo, o que infrinja la privacidad de otra persona.</li>
                        <li>No hacerse pasar por otra persona o entidad, o falsear su afiliación con una persona o entidad.</li>
                        <li>No interferir ni interrumpir el funcionamiento de los Servicios o los servidores o redes conectados a los Servicios.</li>
                        <li>No utilizar ningún robot, araña, raspador u otro medio automatizado para acceder a los Servicios para cualquier propósito sin nuestro permiso expreso por escrito.</li>
                        <li>No recopilar ninguna información de identificación personal de otros usuarios sin su consentimiento expreso.</li>
                    </ul>
                </li>

                <li>
                    <h2>Contenido del Usuario:</h2>
                    <p>Usted conserva la propiedad de los derechos de autor y otros derechos de propiedad intelectual sobre el contenido que carga, publica, transmite o distribuye en o a través de los Servicios ("Contenido del Usuario"). Al enviar Contenido de Usuario, usted nos otorga una licencia mundial, no exclusiva, libre de regalías, sublicenciable y transferible para usar, reproducir, modificar, adaptar, publicar, traducir, crear trabajos derivados, distribuir, realizar y mostrar públicamente su Contenido de Usuario en relación con los Servicios.</p>
                    <p>Usted declara y garantiza que tiene todos los derechos necesarios para otorgarnos la licencia anterior. También declara y garantiza que su Contenido de Usuario no infringe los derechos de propiedad intelectual de terceros.</p>
                    <p>Nos reservamos el derecho de eliminar cualquier Contenido de Usuario que, a nuestra entera discreción, determine que viola estos Términos o que sea objetable.</p>
                </li>

                <li>
                    <h2>Derechos de Propiedad Intelectual:</h2>
                    <p>Los Servicios y todo su contenido, incluidas, entre otras, las marcas comerciales, logotipos, gráficos, imágenes, texto, software, código fuente, audio y video, están protegidos por derechos de autor, marcas comerciales y otras leyes de propiedad intelectual. No puede utilizar ningún contenido de los Servicios sin nuestro permiso expreso por escrito.</p>
                </li>

                <li>
                    <h2>Enlaces a Sitios Web de Terceros:</h2>
                    <p>Los Servicios pueden contener enlaces a sitios web o recursos de terceros. No somos responsables del contenido, las políticas de privacidad o las prácticas de ningún sitio web o recurso de terceros.</p>
                </li>

                <li>
                    <h2>Renuncia de Garantías:</h2>
                    <p>Los Servicios se proporcionan "tal cual" y "según disponibilidad", sin ninguna garantía de ningún tipo, ya sea expresa o implícita, incluidas, entre otras, las garantías implícitas de comerciabilidad, idoneidad para un propósito particular y no infracción.</p>
                </li>

                <li>
                    <h2>Limitación de Responsabilidad:</h2>
                    <p>En ningún caso seremos responsables de ningún daño indirecto, incidental, especial, consecuente o ejemplar, incluidos, entre otros, la pérdida de ganancias, datos o fondo de comercio, que surja del uso o la incapacidad de usar los Servicios, incluso si hemos sido advertidos de la posibilidad de tales daños.</p>
                </li>

                <li>
                    <h2>Indemnización:</h2>
                    <p>Usted acepta indemnizarnos, defendernos y eximirnos de responsabilidad a nosotros y a nuestros afiliados, funcionarios, directores, empleados, agentes, licenciantes y proveedores de y contra todas las reclamaciones, responsabilidades, daños, pérdidas, costos y gastos, incluidos los honorarios razonables de abogados, que surjan de o en relación con su uso de los Servicios o su incumplimiento de estos Términos.</p>
                </li>

                <li>
                    <h2>Modificaciones de los Términos:</h2>
                    <p>Nos reservamos el derecho de modificar estos Términos en cualquier momento. Publicaremos los Términos revisados en los Servicios y la fecha de "última actualización" en la parte superior de estos Términos se revisará. Su uso continuado de los Servicios después de la publicación de los Términos revisados constituye su aceptación de los Términos modificados.</p>
                </li>

                <li>
                    <h2>Ley Aplicable:</h2>
                    <p>Estos Términos se regirán e interpretarán de acuerdo con las leyes de México, sin tener en cuenta sus principios de conflicto de leyes.</p>
                </li>

                <li>
                    <h2>Divisibilidad:</h2>
                    <p>Si alguna disposición de estos Términos se considera inválida o inaplicable, dicha disposición se separará y las disposiciones restantes permanecerán en pleno vigor y efecto.</p>
                </li>

                <li>
                    <h2>Acuerdo Completo:</h2>
                    <p>Estos Términos constituyen el acuerdo completo entre usted y nosotros con respecto a los Servicios y reemplazan todos los acuerdos o entendimientos anteriores o contemporáneos, ya sean orales o escritos.</p>
                </li>

                <li>
                    <h2>Política de Privacidad:</h2>
                    <p>Nuestra Política de Privacidad, que se incorpora a estos Términos por referencia, describe cómo recopilamos, usamos y divulgamos su información personal.</p>
                </li>

                <li>
                    <h2>Contacto:</h2>
                    <p>Si tiene alguna pregunta sobre estos Términos, contáctenos en: <a href="mailto:legal@2upra.com">legal@2upra.com</a></p>
                </li>
            </ol>
        </div>
    </div>
</div>

<?
get_footer();
?>