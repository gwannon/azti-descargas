<?php

/**
 * Plugin Name: Formulario de Descarga de Documentos
 * Description: Shortcode para montar un formulario que el rellenarlo envia un correo con un documento descargable [azti-descarga]
 * Version:     1.0
 * Author:      jorge@enutt.net
 * Author URI:  https://enutt.net/
 * License:     GNU General Public License v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: azti-descargas
 *
 * PHP 8.2
 * WordPress 6.4.1
 */

/* ----------- Multi-idioma ------------------ */
function wpAztiPluginsLoaded() {
	load_plugin_textdomain('azti-descargas', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
}
add_action('plugins_loaded', 'wpAztiPluginsLoaded', 0 );

/* ----------- Includes ------------------ */
include_once(plugin_dir_path(__FILE__).'admin.php');

/* ----------- Shortcode ------------------ */
function wpAztiDescargas($params = array(), $content = null) {
  global $post;
  ob_start(); 
  $control = 0;

  if(defined('ICL_LANGUAGE_NAME')) $current_lang = ICL_LANGUAGE_NAME;
  else $current_lang = get_bloginfo("language");
  
  if(isset($_REQUEST['enviar'])) {
    $recaptcha = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . 
      get_option("_azti_descargas_recaptcha_secreto") . '&response=' . 
      $_REQUEST['recaptcha_response']); 
		$recaptcha = json_decode($recaptcha); 
		if($recaptcha->score > 0.6){
      $headers = array('Content-Type: text/html; charset=UTF-8');
      $emails = explode(",", get_option("_azti_descargas_emails"));
      unset($_REQUEST['enviar']);
      unset($_REQUEST['recaptcha_response']);

      //Guardar datos en CSV
      $f=fopen(__DIR__."/csv/descargas.csv", "a+");
      $csv = $_REQUEST;
      $csv['fecha'] = date("Y-m-d H:i:s");
      $csv['descarga'] = $params['descarga'];
      $csv['url'] = get_the_permalink();
      fputcsv($f, $csv);
      fclose($f);

      //Enviamos email de aviso a los admin
      $mensaje = "Descarga: " . $params['descarga'] . "<br/>";
      foreach($_REQUEST as $item => $value) {
        $mensaje .= $item . ": " . $value . "<br/>";
      }
      foreach($emails as $email) {
        wp_mail(chop($email), "Aviso de descarga de documento AZTI", $mensaje, $headers);
      }

      //Enviamos email de aviso al usuario
      $asunto = get_option("_azti_descargas_asunto_".$current_lang);
      $asunto = str_replace("[descarga-titulo]", $params['descarga-titulo'], $asunto); 
      $mensaje = stripslashes(get_option("_azti_descargas_mensaje_".$current_lang));
      $mensaje = str_replace("[descarga]", $params['descarga'], $mensaje);
      $mensaje = str_replace("[descarga-titulo]", $params['descarga-titulo'], $mensaje);      
      $mensaje = str_replace("[nombre]", strip_tags($_REQUEST['nombre']), $mensaje);
      $mensaje = str_replace("[apellidos]", strip_tags($_REQUEST['apellido']), $mensaje);
      wp_mail(strip_tags($_REQUEST['email']), $asunto, $mensaje, $headers);
      $control = 1;
      ?><h4 class="azti-mensaje"><?php printf(__("Gracias por solicitar el \"%s\". En breve recibirás un email en la dirección que nos has indicado.", "azti-descargas"), $params['descarga-titulo']); ?></h4><?php
    } else {
      ?><h4 class="azti-mensaje error"><?php _e("Hay problemas para establecer si eres o no un robot. Por favor, inténtalo dentro de un rato.", "azti-descargas"); ?></h4><?php
    }
  } ?>
  <div id="descarga-azti">
    <?php if(isset($params['imagen'])) { ?><img src="<?php echo $params['imagen']; ?>" alt="" ><?php } ?>
    <div>
      <h3><?php echo $params['titulo']; ?></h3>
      <?php echo apply_filters("the_content", $content); ?>
    </div>
  </div>
  <?php if($control == 0) { ?> 
    <form id="descarga-azti-form" method="post">
      <!--  -------------- Recaptcha ------------ -->
      <input type="hidden" name="recaptcha_response" id="recaptchaResponse">
      <?php if(!defined('WPCF7_VERSION')) { ?>
      <script src='https://www.google.com/recaptcha/api.js?render=<?php echo get_option("_azti_descargas_recaptcha_sitio"); ?>'></script>
      <script>
        grecaptcha.ready(function() {
          grecaptcha.execute('<?php echo get_option("_azti_descargas_recaptcha_sitio"); ?>', {action: 'comentario'}).then(function(token) {
            var recaptchaResponse = document.getElementById('recaptchaResponse');
            recaptchaResponse.value = token;
          });
        });
      </script><?php } ?>
      <div>
        <label><input type="text" name="nombre" value="" required><span><?php _e("Nombre", "azti-descargas"); ?>*</span></label>
        <label><input type="text" name="apellido" value="" required><span><?php _e("Apellido", "azti-descargas"); ?>*</span></label>
        <label><input type="email" name="email" value="" required><span><?php _e("Email", "azti-descargas"); ?>*</span></label>
        <label><input type="text" name="empresa" value=""><span><?php _e("Empresa", "azti-descargas"); ?></span></label>
        <label><input type="text" name="cargo" value=""><span><?php _e("Cargo", "azti-descargas"); ?></span></label>
        <label><input type="text" name="sector" value=""><span><?php _e("Sector", "azti-descargas"); ?></span></label>
        <label><input type="text" name="pais" value=""><span><?php _e("País", "azti-descargas"); ?></span></label>
        <label><input type="text" name="provincia" value=""><span><?php _e("Provincia", "azti-descargas"); ?></span></label>
      </div>
      <label><input id="azti-acepto-privacidad" type="checkbox" name="acepto-privacidad" value="1"> <?php _e("Acepto la <a href='/politica-de-privacidad/' target='_blank'>politica de privacidad</a>."); ?>*</label>  
      <label><input id="azti-acepto-comunicaciones" type="checkbox" name="acepto-comunicaciones" value="1"> <?php _e("Acepto recibir por email comunicaciones de AZTI."); ?>*</label>  
      <input id="azti-enviar" type="submit" name="enviar" value="<?php _e("Solicitar", "azti-descargas"); ?>" disabled>
    </form>
    <script>
      const privacidad = document.getElementById("azti-acepto-privacidad");
      const comunicaciones = document.getElementById("azti-acepto-comunicaciones");  
      const enviar = document.getElementById('azti-enviar');
      if(privacidad.checked && comunicaciones.checked) enviar.removeAttribute("disabled", "");
      privacidad.addEventListener("click", function (event) {
        if(privacidad.checked && comunicaciones.checked) enviar.removeAttribute("disabled", "");
        else enviar.setAttribute("disabled", "");
      }, false); 
      comunicaciones.addEventListener("click", function (event) {
        if(privacidad.checked && comunicaciones.checked) enviar.removeAttribute("disabled", "");
        else enviar.setAttribute("disabled", "");
      }, false); 
    </script>
  <?php } ?>
  <style>
    #descarga-azti,
    #descarga-azti-form {
      font-family: Gotham, Arial;
    }

    #descarga-azti {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    #descarga-azti img {
      width: auto;
      max-width: 100%;
      margin: 0 auto 20px;
    }

    #descarga-azti h3 {
      padding-top: 0px;
      margin-top: 0px;
    }

    @media (min-width: 500px) {
      #descarga-azti img {
        width: calc(40% - 10px);
      }

      #descarga-azti:has(img) div  {
        width: calc(60% - 10px);
      }
    }

    #descarga-azti-form > div {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 10px;
    }

    #descarga-azti-form > div > label {
      position: relative;
      display: block;
      padding: 20px 0 0 0;
      width: 100%;
    }

    @media (min-width: 500px) {
      #descarga-azti-form > div > label {
        width: calc(50% - 10px);
      }
    }

    #descarga-azti-form label *:is(input[type=text], input[type=email]) {
      background-color: transparent;
      border: none;
      border-bottom: 1px solid #a7a8a7;
      border-radius: 0;
      height: 30px;
      position: relative;
      width: 100%;
      z-index: 1;
    }

    #descarga-azti-form label *:is(input[type=text], input[type=email]) + span {
      bottom: 5px;
      left: 5px;
      position: absolute;
      transform: translate(0);
      transform-origin: 0 0;
      transition: transform .2s ease-in-out;
    }

    #descarga-azti-form label *:is(input[type=text], input[type=email]):focus + span {
      transform: translateY(-23px) scale(.8);
    }

    #descarga-azti-form > label:has(input[type=checkbox]) {
      font-size: 14px;
      line-height: 16px;
      padding: 10px 0 10px 30px;
      width: 100%;
      display: block;
      position: relative;
    }

    #descarga-azti-form > input[type=submit] {
      background-color: transparent;
      border: 1px solid #000;
      color: #000;
      border-radius: 0;
      box-shadow: none;
      padding: 15px 45px;
      transition: all .35s ease-in-out;
      margin-top: 10px;
    }

    #descarga-azti-form > input[type=submit]:hover {
      background-color: #000;
      border: 1px solid #000;
      color: #fff;
    }

    input[disabled] {
      opacity: 0.5;
      cursor: not-allowed;
    }

    #descarga-azti-form > label > input[type="checkbox"] {
      --main-color: #a7a8a7;
      --secondary-color: #fcda01;
      --size: 25px;
      height: var(--size);
      width: var(--size);
      appearance: none;
      outline: none;
      background-color: transparent;
      cursor: pointer;
      position: relative;
      display: inline-block;
      border: 1px solid var(--main-color);
      box-sizing: border-box;
      margin: 0px 4px 0px 0px;
      position: absolute;
      left: 0px;
      top: 3px;
    }

    #descarga-azti-form > label > input[type="checkbox"]:before {
      content: "";
      display: block;
      width: calc(var(--size) - 12px);
      height: calc(var(--size) - 12px);
      position: absolute;
      background-color: var(--secondary-color);
      top: 5px;
      left: 5px;
      transform: scale(0);
      transition: all 0.5s;
    }

    #descarga-azti-form > label > input[type="checkbox"]:checked:before {
      transform: scale(1);
    }

    h4.azti-mensaje {
      font-size: 24px;
      line-height: 28px;
    }

    h4.error.azti-mensaje {
      color: red;
    }


    <?php echo get_option("_azti_descargas_css"); ?>
  </style>
  <?php return ob_get_clean();
}
add_shortcode('azti-descarga', 'wpAztiDescargas');
