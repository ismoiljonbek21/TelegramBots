<?php

function clr($text)
{
    $text = str_replace(['&', '<', '>','{','}'], ['&amp;', '', ''], $text);
    return htmlentities($text, ENT_QUOTES); // | ENT_HTML401


}

