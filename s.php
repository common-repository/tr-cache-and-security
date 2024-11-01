<?php
if($_GET['s']=='img')
{
    require("./inc/phpcaptcha.php");		 

    $aFonts = array('font/VeraBd.ttf');				 
    
    $oVisualCaptcha = new PhpCaptcha($aFonts, 140, 25);
    
    $oVisualCaptcha->UseColour(true);	 
    
    $oVisualCaptcha->SetNumChars(4);
    
    $oVisualCaptcha->SetMinFontSize(16);
    
    $oVisualCaptcha->SetMaxFontSize(18);
    
    $oVisualCaptcha->Create();
}