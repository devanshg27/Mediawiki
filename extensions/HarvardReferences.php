<?php
if ( ! defined( 'MEDIAWIKI' ) )    die();

if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
        $wgHooks['ParserFirstCallInit'][] = 'wfHarvardReferences';
} else {
        $wgExtensionFunctions[] = 'wfHarvardReferences';
}


// Extension credits that will show up on the page [[Special:Version]]    
$wgExtensionCredits['parserhook'][] = array(
    'path'         => __FILE__,
        'name'         => 'HarvardReferences',
        'version'      => '1.0',
        'author'       => 'X-romix', 
        'url'          => 'http://www.mediawiki.org/wiki/Extension:HarvardReferences',
        'description'  => 'Author-date ("Harvard system") referencing style, e.g. Smith 2008:1'
);
 
class HarvardReferences{
        var $SwitchOff = false;
        var $refs = array();
        var $refs_count = array();

        function HarvardReferences() { //Constructor
                $this->setHooks();
        }
        
        function setHooks() {
                global $wgParser, $wgHooks;
                
                //Hook for tag <HarvardReferences/> . Use this tag as <HarvardReferences SwitchOff="yes"/>  for compatibility reasons. 
                //see http://www.mediawiki.org/wiki/Manual:Tag_extensions for details
                $wgParser->setHook( 'HarvardReferences' , array( &$this, 'fnHarvardReferences' ) );
                //function fnHarvardReferences) - is below

                //Hook to ParserBeforeTidy event - "Used to process the nearly-rendered html code for the page (but before any html tidying occurs)"
                //see also http://www.mediawiki.org/wiki/Manual:Hooks/ParserBeforeTidy
                //http://www.mediawiki.org/wiki/Manual:Hooks
                $wgHooks['ParserBeforeTidy'][] = array( &$this, 'fnParserBeforeTidy' );
                //function fnParserBeforeTidy() - is below
        }

        //
        function fnHarvardReferences( $str, $argv, $parser ){
                $s=$argv['SwitchOff'];
                $s=trim(strtolower($s));
        
                if($s=='yes' || $s=='true'){
                        $this->SwitchOff = true;
                }
                return "";
        }

        function fnParserBeforeTidy(&$parser, &$text){
                global $IP;
                if($this->SwitchOff == true){
                        return true;
                }
                $this->romix_log("------");
                
                preg_match_all("/
                        (\[\*)    # [* characters
                        ([^\]]+)  # any text, e.g. Smith 2010
                        (\])      # ] character
                        /x", $text, $matches, PREG_SET_ORDER);
                          
                foreach($matches as $match){
                        $s=$match[2];
                        $this->refs[] = $s; //fill global array refs[]  with names of labels ( e.g. "Smith 2010") 
                }
                
                //process links
                $text=preg_replace_callback("/
                        (\[)                    # [ character
                        \#([^\]\:\*]+)    # any text, e.g. Smith 2010 without ] * : characters
                        (\]|\:[^\]]+\]) # ] character or  :page number etc. and ] character
                        /x", 
                        array( __CLASS__, 'ParseHarvRefCallback' ), 
                        $text);

                //process anchors       
                $text=preg_replace_callback("/
                        (\[\*)      # [* characters
                        ([^\]]+)        # any text, e.g. Smith 2010 without ] characters
                        (\])        # ] character 
                        /x", 
                        array( __CLASS__, 'ParseHarvAnchorsCallback' ), 
                        $text);

                $text.='<script type="text/javascript">
                        function HrvHighlight_Back(prm_name){
                                var anchorTags = document.getElementsByTagName("sup");
                                for (var i = 0; i < anchorTags.length ; i++){
                                        var ob1=anchorTags[i];
                                        if(ob1.id.indexOf("harv_ref-"+prm_name+"-")==0){
                                                ob1.style.backgroundColor="#DDEEFF";
                                        }else{  
                                                ob1.style.backgroundColor="";
                                        }
                                }
                        }
                        
                        function HrvHighlight_Ref(prm_name){
                                var anchorTags = document.getElementsByTagName("sup");
                                for (var i = 0; i < anchorTags.length ; i++){
                                        var ob1=anchorTags[i];
                                        if(ob1.id=="harv_ref-"+prm_name){
                                                ob1.style.backgroundColor="yellow";
                                        }else if(ob1.id=="harv_note-"+prm_name){
                                                ob1.style.backgroundColor="yellow";
                                        }else{  
                                                ob1.style.backgroundColor="";
                                        }
                                }
                        }

                        function HrvHighlight_Note(prm_name){
                                var anchorTags = document.getElementsByTagName("sup");
                                for (var i = 0; i < anchorTags.length ; i++){
                                        var ob1=anchorTags[i];
                                        if(ob1.id=="harv_note-"+prm_name){
                                                ob1.style.backgroundColor="yellow";
                                        }else{  
                                                ob1.style.backgroundColor="";
                                        }
                                }
                        }

                        
                </script>';
                return true;
        }
        
        
        function FormatAsLink($s){
                $from=array(" ", ",", "'");
                $to="_";
                return str_replace($from, $to, $s);
        }
        
        function ParseHarvRefCallback($matches){
                $s=$matches[2];
                $pages=$matches[3];
                $pages=str_replace(']', '', $pages);
                $pages=trim($pages);
                
                if (!in_array($s, $this->refs)) {
                        return $matches[0]; //not do any changes
                }
                
                if(!isset($this->refs_count[$s])){
                        $this->refs_count[$s]=1;
                }else{  
                        $this->refs_count[$s]++;
                }
                
                $cnt=$this->refs_count[$s];
                $name=$this->FormatAsLink($s);
                
                $r='<sup id="harv_ref-'.$name.'-'.$cnt.'" class="reference">'.
                "<a href='#harv_note-".$name."' onClick='HrvHighlight_Ref(".'"'.$name.'-'.$cnt.'"'.")'>".
                "[".$s."]"."</a>".$pages."</sup>";
                
                return $r;
        }


        
        function ParseHarvAnchorsCallback($matches){
                $s=$matches[2];
                
                if(!isset($this->refs_count[$s])){
                        $cnt=0;
                }else{  
                        $cnt=$this->refs_count[$s];
                }       
                
                $name=$this->FormatAsLink($s);
                
                
                if($cnt==0){ // no links to this anchor
                        $r="<sup>[$s]</sup>";
                }else if($cnt==1){ // 1 link to anchor
                        $r='<sup id="harv_note-'.$name.'" class="reference">'.
                        '<a href="#harv_ref-'.$name.'-1" onclick="Javascript:HrvHighlight_Back('."'".$name."'".');">['.$s.']</a> ^ </sup> ';
                        //↑
                }else if($cnt>=2){ // more than 1 link to anchor
                        $r='<sup id="harv_note-'.$name.'" class="reference">'.
                        '<a href="#harv_ref-'.$name.'-1" onclick="Javascript:HrvHighlight_Back('."'".$name."'".');">['.$s.']</a> ^ </sup> ';
                        
                        for($i=1; $i<=$cnt; $i++){
                                $r.=' <sup id="harv_note-'.$name.'-'.$i.'" class="reference"><a href="#harv_ref-'.$name.'-'.$i.'" onclick="Javascript:HrvHighlight_Note('."'".$name.'-'.$i."'".');" class="reference">'.$i.'</a></sup> ';
                        }
                }
                return $r;
        }
        
        function romix_log($s){
                $path = dirname( __FILE__ );
                $fp = fopen ($path."/romix.txt", "a");
                fwrite ($fp, $s . "\n");
                fclose ($fp);
        }       
}

function wfHarvardReferences() {
        new HarvardReferences;
        return true;
} 
?>