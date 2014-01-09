<?php
/*
 * Extension: TagParser
 * Copyright (C) 2007 [[de:User:Revolus]]
 *
 * Provided under the terms of the zlib/libpng License:
 *      http://www.opensource.org/licenses/zlib-license.php
 */
 
$wgExtensionFunctions[] = 'wfTagParser_Setup';
$wgHooks['LanguageGetMagic'][] = 'wfTagParser_Magic';
$wgExtensionCredits['parserhook'][] = array(
        'name' => 'TagParser',
        'description' => 'Providing <tt><nowiki>{{#tag}}</nowiki></tt> to enable parser functions in XML-tags',
        'author' => 'René Kijewski',
        'url' => 'http://www.mediawiki.org/wiki/Extension:TagParser',
);
 
function wfTagParser_Setup() {
        global $wgParser;
        $wgParser->setFunctionHook('tag', 'wfTagParser_Render');
}
 
function wfTagParser_Magic(&$magicWords, $langCode) {
        $magicWords['tag'] = array(0, 'tag');
        return true;
}
 
function wfTagParser_Render( &$parser ) {
        $attributes = func_get_args();
        array_shift($attributes); // 0th parameter is the $parser
        $tag = array_shift($attributes); // the $tag is always given, but it may be empty
        $content = array_pop($attributes); // $content may be NULL
        if($tag === '') {
                $output = '';
        } else {
                if($content === NULL) {
                        $output = '<'. $tag .' />';
                } else {
                        $output = '<' .$tag. ((count($attributes) === 0) ? '' : ' '.implode(' ', $attributes)) .'>'. $content .'</'. $tag .'>';
                }
        }
        return array($output, 'noparse' => false, 'isHTML' => false);
}