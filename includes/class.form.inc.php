<?php
// -----------------------------------------------------------------------------
//                                                                            --
// Haase IT                                                                   --
// class Form                                                                 --
//                                                                            --
// copyright: Marcus Haase (mail@marcus.haase.name)                           --
// The use of this Script is only allowed with the author's authorisation     --
// Any modification of the sources is strictly forbidden                      --
//                                                                            --
// -----------------------------------------------------------------------------

/*
class Form
@Version 0.6
@Author Marcus Haase <mail@marcus.haase.name>
started 10/23/2002

requires: PHP 5.3 and up

Changes 0.5 -> 0.6 (2014-07-09)
Changes code formating to comply with php-fig.org's PSR
Changed variable and method declaration to comply with PHP5's rules

Changes 0.4 -> 0.5 (6.1.2011)
removed (in php 5.3) deprecated funtions split() and ereg()
replaced by explode() and strpos()

Changes 0.3 -> 0.4 (29.5.2008)
makeTextarea now has readonly switch

Changes 0.2 -> 0.3:
now produces valid XHTML code
*/

class Form
{
    public $sFormaction; // Where should the "action" of this form lead?
    public $sFormmethod = 'post'; // Wich method should this form use (POST / GET)?
    public $bUsestyle = false; // Use CSS?
    public $sTextstyle = 'formtext'; // Style of text-input-fields
    public $sUploadstyle = 'formupload'; // Style of upload-input-fields
    public $sTextareastyle = 'formtext'; // Style of textareas
    public $sSelectstyle = 'formselect'; // Style of select-fields
    public $sSubmitstyle = 'formsubmit'; // Style of submitbuttons
    public $sCheckboxstyle = 'formcheckbox'; // Style of checkboxes
    public $sRadiostyle = 'formradio'; // Style of radio-fields
    public $bUploadform = false; // Is this an upload-form?

    // Initialize Class
    public function Form()
    {
        $this->sFormaction = $_SERVER["PHP_SELF"];
    }

    // Opens the form-tag
    // Works without specifying parameters
    // Parameters:
    // $sName = Formname
    // $sTarget = Formtarget
    public function openForm($sName = '', $sTarget = '')
    {
        $sH = '<form action="'.$this->sFormaction.'" method="'.$this->sFormmethod.'"';
        if ($this->bUploadform) {
            $sH .= ' enctype="multipart/form-data"';
        }
        if ($sName != '') {
            $sH .= ' name="'.$sName.'"';
        }
        if ($sTarget != '') {
            $sH .= ' target="'.$sTarget.'"';
        }
        $sH .= '>';
        return $sH;
    }

    // Closes the form-tag
    public function closeForm()
    {
        $sH = '</form>';
        return $sH;
    }

    // Makes a hidden form-field
    // Works without specifying parameters
    // Parematers:
    // $sName = name of the form-field
    // $sValue = value of the form-field
    public function makeHidden($sName = 'sAction', $sValue = 'post')
    {
        $sH = '<input type="hidden" name="'.$sName.'" value="'.$sValue.'">';
        return $sH;
    }

    // Makes a text-input-field
    // Parameters:
    // $sName = name of the form-field
    // $sValue = value of the form-field
    // $iWidth = width of the form-field
    // $iMaxlength maximum length of the input in form-field
    public function makeText($sName, $sValue = '', $iWidth = 0, $iMaxlength = 0, $bReadonly = false, $sClass = '', $sEvents = '')
    {
        $sH = '<input type="text" name="'.$sName.'" value="'.$sValue.'"';
        if ($iMaxlength != 0) {
            $sH .= ' maxlength="'.$iMaxlength.'"';
        }
        if ($sClass != '') {
            $sH .= ' class="'.$sClass.'"';
        } elseif ($this->bUsestyle == true) {
            $sH .= ' class="'.$this->sTextstyle.'"';
        }
        if ($iWidth != 0) {
            $sH .= ' style="width:'.$iWidth.'px;"';
        }
        if ($bReadonly == true) {
            $sH .= ' readonly';
        }
        if ($sEvents != '') {
            $sH .= ' '.$sEvents;
        }
        $sH .= '>';
        return $sH;
    }

    // Makes a password-input-field
    // Parameters:
    // $sName = name of the form-field
    // $sValue = value of the form-field
    // $iWidth = width of the form-field
    // $iMaxlength maximum length of the input in form-field
    public function makePassword($sName, $sValue = '', $iWidth = 0, $iMaxlength = 0, $sClass = '', $sEvents = '')
    {
        $sH = '<input type="password" name="'.$sName.'" value="'.$sValue.'"';
        if ($iMaxlength != 0) {
            $sH .= ' maxlength="'.$iMaxlength.'"';
        }
        if ($sClass != '') {
            $sH .= ' class="'.$sClass.'"';
        } elseif ($this->bUsestyle == true) {
            $sH .= ' class="'.$this->sTextstyle.'"';
        }
        if ($iWidth != 0) {
            $sH .= ' style="width:'.$iWidth.'px;"';
        }
        if ($sEvents != '') {
            $sH .= ' '.$sEvents;
        }
        $sH .= '>';
        return $sH;
    }

    // Makes a Textarea
    // Parameters:
    // $sName = Name of the textarea
    // $sValue = Value of the textarea
    // $iWidth = Width of the textarea
    // $iHeight = Height of the textarea
    // $bReadonly
    public function makeTextarea($sName, $sValue = '', $iWidth = 200, $iHeight = 50, $sClass = '', $sEvents = '', $bReadonly = false)
    {
        $sH = '<textarea cols="8" rows="5" name="'.$sName.'"';
        if ($sClass != '') {
            $sH .= ' class="'.$sClass.'"';
        } elseif ($this->bUsestyle == true) {
            $sH .= ' class="'.$this->sTextareastyle.'"';
        }
        if ($sEvents != '') {
            $sH .= ' '.$sEvents;
        }
        if ($bReadonly) {
            $sH .= ' readonly';
        }
        $sH .= ' style="width:'.$iWidth.'px; height:'.$iHeight.'px;">';
        $sH .= $sValue;
        $sH .= '</textarea>';
        return $sH;
    }

    // Makes a select-field
    // Parameters:
    // $sName = Name of the select-field
    // $aOptions = Array of the Options, if the value and the display are not the
    //             same, seperate them with a pipe ( | ) eg: "value|Text displayed"
    // $sSelected = The value of the option wich should be preselected
    // $iWidth = Width of the select
    // $iSize = Number of rows the select-field should have
    // $bMultipleselections = Shall Multiple selections be allowed?
    // $sEvents = Javascript events
    public function makeSelect($sName, $aOptions, $sSelected = '', $iWidth = 200, $iSize = 1, $bMultipleselections = false, $sEvents = '', $sClass = '')
    {
        $sH = '<select name="'.$sName.'"';
        if ($iSize != 1) {
            $sH .= ' size="'.$iSize.'"';
        }
        if ($bMultipleselections == true) {
            $sH .= ' multiple';
        }
        if ($sClass != '') {
            $sH .= ' class="'.$sClass.'"';
        } elseif ($this->bUsestyle == true) {
            $sH .= ' class="'.$this->sSelectstyle.'"';
        }
        if ($sEvents != '') {
            $sH .= ' '.$sEvents;
        }
        $sH .= ' style="width:'.$iWidth.'px;">';
        foreach ($aOptions as $sOption) {
            if (strpos($sOption, '|') !== false) {
                $aOption = explode('|', $sOption);
                if ($aOption[0] == $sSelected) {
                    $sSelectedtag = ' selected';
                } else {
                    $sSelectedtag = '';
                }
                $sH .= '<option value="'.$aOption[0].'"'.$sSelectedtag;
                if (isset($aOption[2]) && $aOption[2] != '') {
                    $sH .= ' style="'.$aOption[2].'"';
                }
                $sH .= '>'.$aOption[1].'</option>';
                unset($aOption);
            } else {
                if ($sOption == $sSelected) {
                    $sSelectedtag = ' selected';
                } else {
                    $sSelectedtag = '';
                }
                $sH .= '<option value="'.$sOption.'"'.$sSelectedtag.'>'.$sOption.'</option>';
            }
        }
        $sH .= '</select>';
        return $sH;
    }

    // Makes a radio-field
    // Parameters:
    // $sName = Name of the radio-field
    // $sValue = Value of the radio-field
    // $bChecked = Is this field checked?
    public function makeRadio($sName, $sValue, $bChecked = false, $sClass = '', $sEvents = '')
    {
        $sH = '<input type="radio" name="'.$sName.'" value="'.$sValue.'"';
        if($bChecked) {
            $sH .= ' checked';
        }
        if ($sClass != '') {
            $sH .= ' class="'.$sClass.'"';
        } elseif ($this->bUsestyle == true) {
            $sH .= ' class="'.$this->sRadiostyle.'"';
        }
        if ($sEvents != '') {
            $sH .= ' '.$sEvents;
        }
        $sH .= '>';
        return $sH;
    }

    // Makes a checkbox
    // Parameters:
    // $sName = Name of the checkbox
    // $sValue = Value of the checkbox
    // $bChecked = Is this checkbox checked?
    public function makeCheckbox($sName, $sValue, $bChecked = false, $sClass = '', $sEvents = '')
    {
        if (strpos($sName, '|') !== false) {
            $aName = explode('|', $sName);
            $sName = $aName[0];
            $sId = $aName[1];
        }
        $sH = '<input type="checkbox" name="'.$sName.'" value="'.$sValue.'"';
        if (isset($sId)) {
            $sH .= ' id="'.$sId.'"';
        }
        if ($bChecked) {
            $sH .= ' checked';
        }
        if ($sClass != '') {
            $sH .= ' class="'.$sClass.'"';
        } elseif ($this->bUsestyle == true) {
            $sH .= ' class="'.$this->sCheckboxstyle.'"';
        }
        if ($sEvents != '') {
            $sH .= ' '.$sEvents;
        }
        $sH .= '>';
        return $sH;
    }

    // Makes an upload-input-field
    public function makeUpload($sName = '', $sValue = '', $iWidth = 0, $iSize = 0, $sClass = '')
    {
        $sH = '<input type="file" name="'.$sName.'" value="'.$sValue.'"';
        if ($iWidth != 0) {
            $sH .= ' style="width: '.$iWidth.'px;"';
        }
        if ($iSize != 0) {
            $sH .= ' size="40"';
        }
        if ($sClass != '') {
            $sH .= ' class="'.$sClass.'"';
        } elseif ($this->bUsestyle == true) {
            $sH .= ' class="'.$this->sUploadstyle.'"';
        }
        $sH .= '>';
        return $sH;
    }

    // Makes a submit-button
    // Parameters:
    // $sName = Name of the submit-button
    // $sValue = Value of the submit-button
    // $iWidth = Width of the submit-button
    public function makeSubmit($sName = '', $sValue = 'Submit', $iWidth = 0, $sAccesskey = '', $sEvents = '', $sClass = '')
    {
        $sH = '<input type="submit" name="'.$sName.'" value="'.$sValue.'"';
        if ($sClass != '') {
            $sH .= ' class="'.$sClass.'"';
        } elseif ($this->bUsestyle == true) {
            $sH .= ' class="'.$this->sSubmitstyle.'"';
        }
        if ($iWidth != 0) {
            $sH .= ' style="width:'.$iWidth.'px;"';
        }
        if ($sAccesskey != '') {
            $sH .= ' accesskey="'.$sAccesskey.'"';
        }
        if ($sEvents != '') {
            $sH .= ' '.$sEvents;
        }
        $sH .= '>';
        return $sH;
    }

    // Makes an image-submit-button
    // Parameters:
    // $sName = Name of the image-submit-button
    // $sSource = Source of the image-submit-button
    // $sAlt = Alt-text of the image-submit-button
    // $iWidth = Width of the image-submit-button
    // $iHeight = Height of the image-submit-button
    public function makeImage($sName, $sSource, $sAlt = '', $iWidth = 0, $iHeight = 0, $sEvents = '', $sClass = '')
    {
        $sH = '<input type="image" name="'.$sName.'" src="'.$sSource.'"';
        $sH .= ' alt="'.$sAlt.'"';
        if ($iWidth != 0) {
            $sH .= ' width="'.$iWidth.'"';
        }
        if ($iHeight != 0) {
            $sH .= ' height="'.$iHeight.'"';
        }
        if ($sClass != '') {
            $sH .= ' class="'.$sClass.'"';
        } elseif ($this->bUsestyle == true) {
            $sH .= ' class="'.$this->sSubmitstyle.'"';
        }
        if ($sEvents != '') {
            $sH .= ' '.$sEvents;
        }
        $sH .= '>';
        return $sH;
    }

    // Makes an reset-button
    // Parameters:
    // $sName = Name of the reset-button
    // $sValue = Value of the reset-button
    public function makeReset($sName = '', $sValue = 'Reset', $iWidth = 0, $sClass = '', $sEvents = '')
    {
        $sH = '<input type="reset" name="'.$sName.'" value="'.$sValue.'"';
        if ($sClass != '') {
            $sH .= ' class="'.$sClass.'"';
        } elseif ($this->bUsestyle == true) {
            $sH .= ' class="'.$this->sSubmitstyle.'"';
        }
        if ($iWidth != 0) {
            $sH .= ' style="width:'.$iWidth.'px;"';
        }
        if ($sEvents != '') {
            $sH .= ' '.$sEvents;
        }
        $sH .= '>';
        return $sH;
    }
}
