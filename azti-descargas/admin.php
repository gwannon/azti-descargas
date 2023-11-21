<?php

//Administrador --------------------- 
add_action( 'admin_menu', 'wpatg_plugin_menu' );
function wpatg_plugin_menu() {
	add_options_page( __('Descargas', 'azti-descargas'), __('Formularios Descargas', 'azti-descargas'), 'manage_options', 'azti-descargas', 'wpAztiAdminPage');
}

function wpAztiAdminPage() { 
  $langs = array("es" => "Castellano", "eu" => "Euskera", "en" => "Inglés");
  $settings = array( 'media_buttons' => true, 'quicktags' => true, 'textarea_rows' => 15 ); ?>
  <h1><?php _e("Configuración de Formularios de Descarga", 'azti-descargas'); ?></h1>
  <a href="<?php echo get_admin_url(); ?>options-general.php?page=azti-descargas&csv=true" class="button"><?php _e("Exportar a CSV", 'azti-descargas'); ?></a>
  <?php if(isset($_REQUEST['send']) && $_REQUEST['send'] != '') { 
    ?><p style="border: 1px solid green; color: green; text-align: center;"><?php _e("Datos guardados correctamente.", 'azti-descargas'); ?></p><?php
    update_option('_azti_descargas_recaptcha_sitio', $_POST['_azti_descargas_recaptcha_sitio']);
    update_option('_azti_descargas_recaptcha_secreto', $_POST['_azti_descargas_recaptcha_secreto']);
    update_option('_azti_descargas_emails', $_POST['_azti_descargas_emails']);
    foreach ($langs as $label => $lang) {
      update_option('_azti_descargas_asunto_'.$label, $_POST['_azti_descargas_asunto_'.$label]);
      update_option('_azti_descargas_mensaje_'.$label, $_POST['_azti_descargas_mensaje_'.$label]);
    }
    update_option('_azti_descargas_css', $_POST['_azti_descargas_css']);
  } ?>
  <form method="post">
    <h2><?php _e("Configuración reCAPTCHA (v3)", 'azti-descargas'); ?></h2>
    <b><?php _e("reCAPTCHA (v3) Clave del sitio", 'azti-descargas'); ?>:</b><br/>
    <input type="text" name="_azti_descargas_recaptcha_sitio" value="<?php echo get_option("_azti_descargas_recaptcha_sitio"); ?>" style="width: calc(100% - 20px);" /><br/><br/>
    <b><?php _e("reCAPTCHA (v3) Clave secreta", 'azti-descargas'); ?>:</b><br/>
    <input type="text" name="_azti_descargas_recaptcha_secreto" value="<?php echo get_option("_azti_descargas_recaptcha_secreto"); ?>" style="width: calc(100% - 20px);" /><br/><br/>
    <h2><?php _e("Configuración envío de emails", 'azti-descargas'); ?></h2>
    <b><?php _e("Emails a los que avisar de la descarga", 'azti-descargas'); ?> <small>(<?php _e("Separados por comas", 'azti-descargas'); ?>)</small>:</b><br/>
    <input type="text" name="_azti_descargas_emails" value="<?php echo get_option("_azti_descargas_emails"); ?>" style="width: calc(100% - 20px);" /><br/><br/>
    <?php foreach ($langs as $label => $lang) { ?>
      <b><?php echo $lang; ?></b><br/>
      <b><?php _e("Asunto del email de descarga", 'azti-descargas'); ?>:</b><br/>
      <input type="text" name="_azti_descargas_asunto_<?php echo $label; ?>" value="<?php echo get_option("_azti_descargas_asunto_".$label); ?>" style="width: calc(100% - 20px);" /><br/>
      <ul>
        <li>[descarga-titulo]: <?php _e("nombre del documento a descargar", 'azti-descargas'); ?></li>
      </ul><br/>
      <b><?php _e("Diseño del email de descarga", 'azti-descargas'); ?>:</b><br/>
      <?php wp_editor( stripslashes(get_option("_azti_descargas_mensaje_".$label)), '_azti_descargas_mensaje_'.$label, $settings ); ?><br/>
    <?php } ?>
    <ul>
      <li>[descarga]: <?php _e("url del documento a descargar", 'azti-descargas'); ?></li>
      <li>[descarga-titulo]: <?php _e("nombre del documento a descargar", 'azti-descargas'); ?></li>
      <li>[nombre]: <?php _e("nombre del usuario", 'azti-descargas'); ?></li>
      <li>[apellido]: <?php _e("apellido del usuario", 'azti-descargas'); ?></li>
    </ul>
    <br/><br/>
    <b><?php _e("Código CSS", 'azti-descargas'); ?>:</b><br/>  
    <textarea name="_azti_descargas_css" style="width: 100%; height: 330px;"><?php echo get_option("_azti_descargas_css"); ?></textarea>
    <br/><br/>
    <input type="submit" name="send" class="button button-primary" value="<?php _e("Guardar", 'azti-descargas'); ?>" />
  </form>
<?php }

//Exportar a CSV ---------------------
function wpAztiExportToCSV() {
  if (isset($_GET['page']) && $_GET['page'] == 'azti-descargas' && isset($_GET['csv']) && $_GET['csv'] == 'true') {
    $csv = "Nombre,Apellido,email,Empresa,Cargo,Sector,Pais,Provincia,Acepto comunicaciones,Acepto política de privacidad,Fecha,Fichero,Url"."\n";
		$f = fopen(__DIR__."/csv/descargas.csv", "a+");
    while (($datos = fgetcsv($f, 0, ",")) !== FALSE) {
      $csv .= "\"".implode('","', $datos)."\""."\n";
    }
    fclose($f);
		
		$now = gmdate("D, d M Y H:i:s");
		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
		header("Last-Modified: {$now} GMT");

		// force download
		header("Content-Description: File Transfer");
		header("Content-Encoding: UTF-8");
		header("Content-Type: text/csv; charset=UTF-8");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");

		// disposition / encoding on response body
		header("Content-Disposition: attachment;filename=azti-descargas-".date("Y-m-d_His").".csv");
		header("Content-Transfer-Encoding: binary");
		echo $csv;
		die;
  }
}
add_action( 'admin_init', 'wpAztiExportToCSV', 1 );