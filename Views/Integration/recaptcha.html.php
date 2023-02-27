<?php

$defaultInputClass = (isset($inputClass)) ? $inputClass : 'input';
$containerType     = 'div-wrapper';

include __DIR__.'/../../../../app/bundles/FormBundle/Views/Field/field_helper.php';

$action   = $app->getRequest()->get('objectAction');
$settings = $field['properties'];

$formName    = str_replace('_', '', $formName);
$hashedFormName = md5($formName);
$formButtons = (!empty($inForm)) ? $view->render(
    'MauticFormBundle:Builder:actions.html.php',
    [
        'deleted'        => false,
        'id'             => $id,
        'formId'         => $formId,
        'formName'       => $formName,
        'disallowDelete' => false,
    ]
) : '';

$label = (!$field['showLabel'])
    ? ''
    : <<<HTML
<label $labelAttr>{$view->escape($field['label'])}</label>
HTML;

$jsElement = <<<JSELEMENT
	<script type="text/javascript">
    function verifyCallback_{$hashedFormName}( response ) {
        document.getElementById("mauticform_input_{$formName}_{$field['alias']}").value = response;
    }
    function onLoad{$hashedFormName}() { 
        grecaptcha.execute('{$field['customParameters']['site_key']}', {action: 'form'}).then(function(token) {
            verifyCallback_{$hashedFormName}(token);
         }); 
    }
</script>
JSELEMENT;

$JSSrc = "";
if($field['customParameters']['version'] == 'v2') {
    $JSSrc = "https://www.google.com/recaptcha/api.js";
} else {
    $JSSrc = "https://www.google.com/recaptcha/api.js?onload=onLoad{$hashedFormName}&render={$field['customParameters']['site_key']}";
}
?>
<script>
function recaptchaCheck(checkbox) {
    if(checkbox.checked == true){
        var el = document.getElementById("captcha_request");
        if (el) {
            el.innerHTML = "";
            var sc = document.createElement("script");
            sc.src = "<?php echo $JSSrc; ?>";
            el.appendChild(sc);
        }
    }
}
</script>
<div id="captcha_request">
<input type="checkbox" id="recapchacheck" name="recapchacheck" value="OK" onchange="recaptchaCheck(this);">
<label for="recapchacheck"> Nachladen von Captcha von google.com (Achtung: hierbei setzt Google cookies ein!)</label>
</div>
<?php

$html = <<<HTML
    {$jsElement}
	<div $containerAttr>
        {$label}
HTML;

if($field['customParameters']['version'] == 'v2') {
$html .= <<<HTML
<div class="g-recaptcha" data-sitekey="{$field['customParameters']['site_key']}" data-callback="verifyCallback_{$hashedFormName}"></div>
HTML;
}

$html .= <<<HTML
        <input $inputAttr type="hidden">
        <span class="mauticform-errormsg" style="display: none;"></span>
    </div>
HTML;
?>



<?php
echo $html;
?>

