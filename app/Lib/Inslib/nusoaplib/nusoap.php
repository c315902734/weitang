<?php

/*
$Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $

NuSOAP - Web Services Toolkit for PHP

Copyright (c) 2002 NuSphere Corporation

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

The NuSOAP project home is:
http://sourceforge.net/projects/nusoap/

The primary support for NuSOAP is the Help forum on the project home page.

If you have any questions or comments, please email:

Dietrich Ayala
dietrich@ganx4.com
http://dietrich.ganx4.com/nusoap

NuSphere Corporation
http://www.nusphere.com

*/

/*
 *	Some of the standards implmented in whole or part by NuSOAP:
 *
 *	SOAP 1.1 (http://www.w3.org/TR/2000/NOTE-SOAP-20000508/)
 *	WSDL 1.1 (http://www.w3.org/TR/2001/NOTE-wsdl-20010315)
 *	SOAP Messages With Attachments (http://www.w3.org/TR/SOAP-attachments)
 *	XML 1.0 (http://www.w3.org/TR/2006/REC-xml-20060816/)
 *	Namespaces in XML 1.0 (http://www.w3.org/TR/2006/REC-xml-names-20060816/)
 *	XML Schema 1.0 (http://www.w3.org/TR/xmlschema-0/)
 *	RFC 2045 Multipurpose Internet Mail Extensions (MIME) Part One: Format of Internet Message Bodies
 *	RFC 2068 Hypertext Transfer Protocol -- HTTP/1.1
 *	RFC 2617 HTTP Authentication: Basic and Digest Access Authentication
 */

/* load classes

// necessary classes
require_once('class.soapclient.php');
require_once('class.soap_val.php');
require_once('class.soap_parser.php');
require_once('class.soap_fault.php');

// transport classes
require_once('class.soap_transport_http.php');

// optional add-on classes
require_once('class.xmlschema.php');
require_once('class.wsdl.php');

// server class
require_once('class.soap_server.php');*/

// class variable emulation
// cf. http://www.webkreator.com/php/techniques/php-static-class-variables.html
$GLOBALS['_transient']['static']['nusoap_base']['globalDebugLevel'] = 9;

/**
*
* nusoap_base
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @author   Scott Nichol <snichol@users.sourceforge.net>
* @version  $Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $
* @access   public
*/
class nusoap_base {
	/**
	 * Identification for HTTP headers.
	 *
	 * @var string
	 * @access private
	 */
	var $title = 'NuSOAP';
	/**
	 * Version for HTTP headers.
	 *
	 * @var string
	 * @access private
	 */
	var $version = '0.9.5';
	/**
	 * CVS revision for HTTP headers.
	 *
	 * @var string
	 * @access private
	 */
	var $revision = '$Revision: 1.123 $';
    /**
     * Current error string (manipulated by getError/setError)
	 *
	 * @var string
	 * @access private
	 */
	var $error_str = '';
    /**
     * Current debug string (manipulated by debug/appendDebug/clearDebug/getDebug/getDebugAsXMLComment)
	 *
	 * @var string
	 * @access private
	 */
    var $debug_str = '';
    /**
	 * toggles automatic encoding of special characters as entities
	 * (should always be true, I think)
	 *
	 * @var boolean
	 * @access private
	 */
	var $charencoding = true;
	/**
	 * the debug level for this instance
	 *
	 * @var	integer
	 * @access private
	 */
	var $debugLevel;

    /**
	* set schema version
	*
	* @var      string
	* @access   public
	*/
	var $XMLSchemaVersion = 'http://www.w3.org/2001/XMLSchema';
	
    /**
	* charset encoding for outgoing messages
	*
	* @var      string
	* @access   public
	*/
    var $soap_defencoding = 'ISO-8859-1';
	//var $soap_defencoding = 'UTF-8';

	/**
	* namespaces in an array of prefix => uri
	*
	* this is "seeded" by a set of constants, but it may be altered by code
	*
	* @var      array
	* @access   public
	*/
	var $namespaces = array(
		'SOAP-ENV' => 'http://schemas.xmlsoap.org/soap/envelope/',
		'xsd' => 'http://www.w3.org/2001/XMLSchema',
		'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
		'SOAP-ENC' => 'http://schemas.xmlsoap.org/soap/encoding/'
		);

	/**
	* namespaces used in the current context, e.g. during serialization
	*
	* @var      array
	* @access   private
	*/
	var $usedNamespaces = array();

	/**
	* XML Schema types in an array of uri => (array of xml type => php type)
	* is this legacy yet?
	* no, this is used by the nusoap_xmlschema class to verify type => namespace mappings.
	* @var      array
	* @access   public
	*/
	var $typemap = array(
	'http://www.w3.org/2001/XMLSchema' => array(
		'string'=>'string','boolean'=>'boolean','float'=>'double','double'=>'double','decimal'=>'double',
		'duration'=>'','dateTime'=>'string','time'=>'string','date'=>'string','gYearMonth'=>'',
		'gYear'=>'','gMonthDay'=>'','gDay'=>'','gMonth'=>'','hexBinary'=>'string','base64Binary'=>'string',
		// abstract "any" types
		'anyType'=>'string','anySimpleType'=>'string',
		// derived datatypes
		'normalizedString'=>'string','token'=>'string','language'=>'','NMTOKEN'=>'','NMTOKENS'=>'','Name'=>'','NCName'=>'','ID'=>'',
		'IDREF'=>'','IDREFS'=>'','ENTITY'=>'','ENTITIES'=>'','integer'=>'integer','nonPositiveInteger'=>'integer',
		'negativeInteger'=>'integer','long'=>'integer','int'=>'integer','short'=>'integer','byte'=>'integer','nonNegativeInteger'=>'integer',
		'unsignedLong'=>'','unsignedInt'=>'','unsignedShort'=>'','unsignedByte'=>'','positiveInteger'=>''),
	'http://www.w3.org/2000/10/XMLSchema' => array(
		'i4'=>'','int'=>'integer','boolean'=>'boolean','string'=>'string','double'=>'double',
		'float'=>'double','dateTime'=>'string',
		'timeInstant'=>'string','base64Binary'=>'string','base64'=>'string','ur-type'=>'array'),
	'http://www.w3.org/1999/XMLSchema' => array(
		'i4'=>'','int'=>'integer','boolean'=>'boolean','string'=>'string','double'=>'double',
		'float'=>'double','dateTime'=>'string',
		'timeInstant'=>'string','base64Binary'=>'string','base64'=>'string','ur-type'=>'array'),
	'http://soapinterop.org/xsd' => array('SOAPStruct'=>'struct'),
	'http://schemas.xmlsoap.org/soap/encoding/' => array('base64'=>'string','array'=>'array','Array'=>'array'),
    'http://xml.apache.org/xml-soap' => array('Map')
	);

	/**
	* XML entities to convert
	*
	* @var      array
	* @access   public
	* @deprecated
	* @see	expandEntities
	*/
	var $xmlEntities = array('quot' => '"','amp' => '&',
		'lt' => '<','gt' => '>','apos' => "'");

	/**
	* constructor
	*
	* @access	public
	*/
	function nusoap_base() {
		$this->debugLevel = $GLOBALS['_transient']['static']['nusoap_base']['globalDebugLevel'];
	}

	/**
	* gets the global debug level, which applies to future instances
	*
	* @return	integer	Debug level 0-9, where 0 turns off
	* @access	public
	*/
	function getGlobalDebugLevel() {
		return $GLOBALS['_transient']['static']['nusoap_base']['globalDebugLevel'];
	}

	/**
	* sets the global debug level, which applies to future instances
	*
	* @param	int	$level	Debug level 0-9, where 0 turns off
	* @access	public
	*/
	function setGlobalDebugLevel($level) {
		$GLOBALS['_transient']['static']['nusoap_base']['globalDebugLevel'] = $level;
	}

	/**
	* gets the debug level for this instance
	*
	* @return	int	Debug level 0-9, where 0 turns off
	* @access	public
	*/
	function getDebugLevel() {
		return $this->debugLevel;
	}

	/**
	* sets the debug level for this instance
	*
	* @param	int	$level	Debug level 0-9, where 0 turns off
	* @access	public
	*/
	function setDebugLevel($level) {
		$this->debugLevel = $level;
	}

	/**
	* adds debug data to the instance debug string with formatting
	*
	* @param    string $string debug data
	* @access   private
	*/
	function debug($string){
		if ($this->debugLevel > 0) {
			$this->appendDebug($this->getmicrotime().' '.get_class($this).": $string\n");
		}
	}

	/**
	* adds debug data to the instance debug string without formatting
	*
	* @param    string $string debug data
	* @access   public
	*/
	function appendDebug($string){
		if ($this->debugLevel > 0) {
			// it would be nice to use a memory stream here to use
			// memory more efficiently
			$this->debug_str .= $string;
		}
	}

	/**
	* clears the current debug data for this instance
	*
	* @access   public
	*/
	function clearDebug() {
		// it would be nice to use a memory stream here to use
		// memory more efficiently
		$this->debug_str = '';
	}

	/**
	* gets the current debug data for this instance
	*
	* @return   debug data
	* @access   public
	*/
	function &getDebug() {
		// it would be nice to use a memory stream here to use
		// memory more efficiently
		return $this->debug_str;
	}

	/**
	* gets the current debug data for this instance as an XML comment
	* this may change the contents of the debug data
	*
	* @return   debug data as an XML comment
	* @access   public
	*/
	function &getDebugAsXMLComment() {
		// it would be nice to use a memory stream here to use
		// memory more efficiently
		while (strpos($this->debug_str, '--')) {
			$this->debug_str = str_replace('--', '- -', $this->debug_str);
		}
		$ret = "<!--\n" . $this->debug_str . "\n-->";
    	return $ret;
	}

	/**
	* expands entities, e.g. changes '<' to '&lt;'.
	*
	* @param	string	$val	The string in which to expand entities.
	* @access	private
	*/
	function expandEntities($val) {
		if ($this->charencoding) {
	    	$val = str_replace('&', '&amp;', $val);
	    	$val = str_replace("'", '&apos;', $val);
	    	$val = str_replace('"', '&quot;', $val);
	    	$val = str_replace('<', '&lt;', $val);
	    	$val = str_replace('>', '&gt;', $val);
	    }
	    return $val;
	}

	/**
	* returns error string if present
	*
	* @return   mixed error string or false
	* @access   public
	*/
	function getError(){
		if($this->error_str != ''){
			return $this->error_str;
		}
		return false;
	}

	/**
	* sets error string
	*
	* @return   boolean $string error string
	* @access   private
	*/
	function setError($str){
		$this->error_str = $str;
	}

	/**
	* detect if array is a simple array or a struct (associative array)
	*
	* @param	mixed	$val	The PHP array
	* @return	string	(arraySimple|arrayStruct)
	* @access	private
	*/
	function isArraySimpleOrStruct($val) {
        $keyList = array_keys($val);
		foreach ($keyList as $keyListValue) {
			if (!is_int($keyListValue)) {
				return 'arrayStruct';
			}
		}
		return 'arraySimple';
	}

	/**
	* serializes PHP values in accordance w/ section 5. Type information is
	* not serialized if $use == 'literal'.
	*
	* @param	mixed	$val	The value to serialize
	* @param	string	$name	The name (local part) of the XML element
	* @param	string	$type	The XML schema type (local part) for the element
	* @param	string	$name_ns	The namespace for the name of the XML element
	* @param	string	$type_ns	The namespace for the type of the element
	* @param	array	$attributes	The attributes to serialize as name=>value pairs
	* @param	string	$use	The WSDL "use" (encoded|literal)
	* @param	boolean	$soapval	Whether this is called from soapval.
	* @return	string	The serialized element, possibly with child elements
    * @access	public
	*/
	function serialize_val($val,$name=false,$type=false,$name_ns=false,$type_ns=false,$attributes=false,$use='encoded',$soapval=false) {
		$this->debug("in serialize_val: name=$name, type=$type, name_ns=$name_ns, type_ns=$type_ns, use=$use, soapval=$soapval");
		$this->appendDebug('value=' . $this->varDump($val));
		$this->appendDebug('attributes=' . $this->varDump($attributes));
		
    	if (is_object($val) && get_class($val) == 'soapval' && (! $soapval)) {
    		$this->debug("serialize_val: serialize soapval");
        	$xml = $val->serialize($use);
			$this->appendDebug($val->getDebug());
			$val->clearDebug();
			$this->debug("serialize_val of soapval returning $xml");
			return $xml;
        }
		// force valid name if necessary
		if (is_numeric($name)) {
			$name = '__numeric_' . $name;
		} elseif (! $name) {
			$name = 'noname';
		}
		// if name has ns, add ns prefix to name
		$xmlns = '';
        if($name_ns){
			$prefix = 'nu'.rand(1000,9999);
			$name = $prefix.':'.$name;
			$xmlns .= " xmlns:$prefix=\"$name_ns\"";
		}
		// if type is prefixed, create type prefix
		if($type_ns != '' && $type_ns == $this->namespaces['xsd']){
			// need to fix this. shouldn't default to xsd if no ns specified
		    // w/o checking against typemap
			$type_prefix = 'xsd';
		} elseif($type_ns){
			$type_prefix = 'ns'.rand(1000,9999);
			$xmlns .= " xmlns:$type_prefix=\"$type_ns\"";
		}
		// serialize attributes if present
		$atts = '';
		if($attributes){
			foreach($attributes as $k => $v){
				$atts .= " $k=\"".$this->expandEntities($v).'"';
			}
		}
		// serialize null value
		if (is_null($val)) {
    		$this->debug("serialize_val: serialize null");
			if ($use == 'literal') {
				// TODO: depends on minOccurs
				$xml = "<$name$xmlns$atts/>";
				$this->debug("serialize_val returning $xml");
	        	return $xml;
        	} else {
				if (isset($type) && isset($type_prefix)) {
					$type_str = " xsi:type=\"$type_prefix:$type\"";
				} else {
					$type_str = '';
				}
				$xml = "<$name$xmlns$type_str$atts xsi:nil=\"true\"/>";
				$this->debug("serialize_val returning $xml");
	        	return $xml;
        	}
		}
        // serialize if an xsd built-in primitive type
        if($type != '' && isset($this->typemap[$this->XMLSchemaVersion][$type])){
    		$this->debug("serialize_val: serialize xsd built-in primitive type");
        	if (is_bool($val)) {
        		if ($type == 'boolean') {
	        		$val = $val ? 'true' : 'false';
	        	} elseif (! $val) {
	        		$val = 0;
	        	}
			} else if (is_string($val)) {
				$val = $this->expandEntities($val);
			}
			if ($use == 'literal') {
				$xml = "<$name$xmlns$atts>$val</$name>";
				$this->debug("serialize_val returning $xml");
	        	return $xml;
        	} else {
				$xml = "<$name$xmlns xsi:type=\"xsd:$type\"$atts>$val</$name>";
				$this->debug("serialize_val returning $xml");
	        	return $xml;
        	}
        }
		// detect type and serialize
		$xml = '';
		switch(true) {
			case (is_bool($val) || $type == 'boolean'):
		   		$this->debug("serialize_val: serialize boolean");
        		if ($type == 'boolean') {
	        		$val = $val ? 'true' : 'false';
	        	} elseif (! $val) {
	        		$val = 0;
	        	}
				if ($use == 'literal') {
					$xml .= "<$name$xmlns$atts>$val</$name>";
				} else {
					$xml .= "<$name$xmlns xsi:type=\"xsd:boolean\"$atts>$val</$name>";
				}
				break;
			case (is_int($val) || is_long($val) || $type == 'int'):
		   		$this->debug("serialize_val: serialize int");
				if ($use == 'literal') {
					$xml .= "<$name$xmlns$atts>$val</$name>";
				} else {
					$xml .= "<$name$xmlns xsi:type=\"xsd:int\"$atts>$val</$name>";
				}
				break;
			case (is_float($val)|| is_double($val) || $type == 'float'):
		   		$this->debug("serialize_val: serialize float");
				if ($use == 'literal') {
					$xml .= "<$name$xmlns$atts>$val</$name>";
				} else {
					$xml .= "<$name$xmlns xsi:type=\"xsd:float\"$atts>$val</$name>";
				}
				break;
			case (is_string($val) || $type == 'string'):
		   		$this->debug("serialize_val: serialize string");
				$val = $this->expandEntities($val);
				if ($use == 'literal') {
					$xml .= "<$name$xmlns$atts>$val</$name>";
				} else {
					$xml .= "<$name$xmlns xsi:type=\"xsd:string\"$atts>$val</$name>";
				}
				break;
			case is_object($val):
		   		$this->debug("serialize_val: serialize object");
		    	if (get_class($val) == 'soapval') {
		    		$this->debug("serialize_val: serialize soapval object");
		        	$pXml = $val->serialize($use);
					$this->appendDebug($val->getDebug());
					$val->clearDebug();
		        } else {
					if (! $name) {
						$name = get_class($val);
						$this->debug("In serialize_val, used class name $name as element name");
					} else {
						$this->debug("In serialize_val, do not override name $name for element name for class " . get_class($val));
					}
					foreach(get_object_vars($val) as $k => $v){
						$pXml = isset($pXml) ? $pXml.$this->serialize_val($v,$k,false,false,false,false,$use) : $this->serialize_val($v,$k,false,false,false,false,$use);
					}
				}
				if(isset($type) && isset($type_prefix)){
					$type_str = " xsi:type=\"$type_prefix:$type\"";
				} else {
					$type_str = '';
				}
				if ($use == 'literal') {
					$xml .= "<$name$xmlns$atts>$pXml</$name>";
				} else {
					$xml .= "<$name$xmlns$type_str$atts>$pXml</$name>";
				}
				break;
			break;
			case (is_array($val) || $type):
				// detect if struct or array
				$valueType = $this->isArraySimpleOrStruct($val);
                if($valueType=='arraySimple' || preg_match('/^ArrayOf/',$type)){
			   		$this->debug("serialize_val: serialize array");
					$i = 0;
					if(is_array($val) && count($val)> 0){
						foreach($val as $v){
	                    	if(is_object($v) && get_class($v) ==  'soapval'){
								$tt_ns = $v->type_ns;
								$tt = $v->type;
							} elseif (is_array($v)) {
								$tt = $this->isArraySimpleOrStruct($v);
							} else {
								$tt = gettype($v);
	                        }
							$array_types[$tt] = 1;
							// TODO: for literal, the name should be $name
							$xml .= $this->serialize_val($v,'item',false,false,false,false,$use);
							++$i;
						}
						if(count($array_types) > 1){
							$array_typename = 'xsd:anyType';
						} elseif(isset($tt) && isset($this->typemap[$this->XMLSchemaVersion][$tt])) {
							if ($tt == 'integer') {
								$tt = 'int';
							}
							$array_typename = 'xsd:'.$tt;
						} elseif(isset($tt) && $tt == 'arraySimple'){
							$array_typename = 'SOAP-ENC:Array';
						} elseif(isset($tt) && $tt == 'arrayStruct'){
							$array_typename = 'unnamed_struct_use_soapval';
						} else {
							// if type is prefixed, create type prefix
							if ($tt_ns != '' && $tt_ns == $this->namespaces['xsd']){
								 $array_typename = 'xsd:' . $tt;
							} elseif ($tt_ns) {
								$tt_prefix = 'ns' . rand(1000, 9999);
								$array_typename = "$tt_prefix:$tt";
								$xmlns .= " xmlns:$tt_prefix=\"$tt_ns\"";
							} else {
								$array_typename = $tt;
							}
						}
						$array_type = $i;
						if ($use == 'literal') {
							$type_str = '';
						} else if (isset($type) && isset($type_prefix)) {
							$type_str = " xsi:type=\"$type_prefix:$type\"";
						} else {
							$type_str = " xsi:type=\"SOAP-ENC:Array\" SOAP-ENC:arrayType=\"".$array_typename."[$array_type]\"";
						}
					// empty array
					} else {
						if ($use == 'literal') {
							$type_str = '';
						} else if (isset($type) && isset($type_prefix)) {
							$type_str = " xsi:type=\"$type_prefix:$type\"";
						} else {
							$type_str = " xsi:type=\"SOAP-ENC:Array\" SOAP-ENC:arrayType=\"xsd:anyType[0]\"";
						}
					}
					// TODO: for array in literal, there is no wrapper here
					$xml = "<$name$xmlns$type_str$atts>".$xml."</$name>";
				} else {
					// got a struct
			   		$this->debug("serialize_val: serialize struct");
					if(isset($type) && isset($type_prefix)){
						$type_str = " xsi:type=\"$type_prefix:$type\"";
					} else {
						$type_str = '';
					}
					if ($use == 'literal') {
						$xml .= "<$name$xmlns$atts>";
					} else {
						$xml .= "<$name$xmlns$type_str$atts>";
					}
					foreach($val as $k => $v){
						// Apache Map
						if ($type == 'Map' && $type_ns == 'http://xml.apache.org/xml-soap') {
							$xml .= '<item>';
							$xml .= $this->serialize_val($k,'key',false,false,false,false,$use);
							$xml .= $this->serialize_val($v,'value',false,false,false,false,$use);
							$xml .= '</item>';
						} else {
							$xml .= $this->serialize_val($v,$k,false,false,false,false,$use);
						}
					}
					$xml .= "</$name>";
				}
				break;
			default:
		   		$this->debug("serialize_val: serialize unknown");
				$xml .= 'not detected, got '.gettype($val).' for '.$val;
				break;
		}
		$this->debug("serialize_val returning $xml");
		return $xml;
	}

    /**
    * serializes a message
    *
    * @param string $body the XML of the SOAP body
    * @param mixed $headers optional string of XML with SOAP header content, or array of soapval objects for SOAP headers, or associative array
    * @param array $namespaces optional the namespaces used in generating the body and headers
    * @param string $style optional (rpc|document)
    * @param string $use optional (encoded|literal)
    * @param string $encodingStyle optional (usually 'http://schemas.xmlsoap.org/soap/encoding/' for encoded)
    * @return string the message
    * @access public
    */
    function serializeEnvelope($body,$headers=false,$namespaces=array(),$style='rpc',$use='encoded',$encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'){
    // TODO: add an option to automatically run utf8_encode on $body and $headers
    // if $this->soap_defencoding is UTF-8.  Not doing this automatically allows
    // one to send arbitrary UTF-8 characters, not just characters that map to ISO-8859-1

	$this->debug("In serializeEnvelope length=" . strlen($body) . " body (max 1000 characters)=" . substr($body, 0, 1000) . " style=$style use=$use encodingStyle=$encodingStyle");
	$this->debug("headers:");
	$this->appendDebug($this->varDump($headers));
	$this->debug("namespaces:");
	$this->appendDebug($this->varDump($namespaces));

	// serialize namespaces
    $ns_string = '';
	foreach(array_merge($this->namespaces,$namespaces) as $k => $v){
		$ns_string .= " xmlns:$k=\"$v\"";
	}
	if($encodingStyle) {
		$ns_string = " SOAP-ENV:encodingStyle=\"$encodingStyle\"$ns_string";
	}

	// serialize headers
	if($headers){
		if (is_array($headers)) {
			$xml = '';
			foreach ($headers as $k => $v) {
				if (is_object($v) && get_class($v) == 'soapval') {
					$xml .= $this->serialize_val($v, false, false, false, false, false, $use);
				} else {
					$xml .= $this->serialize_val($v, $k, false, false, false, false, $use);
				}
			}
			$headers = $xml;
			$this->debug("In serializeEnvelope, serialized array of headers to $headers");
		}
		$headers = "<SOAP-ENV:Header>".$headers."</SOAP-ENV:Header>";
	}
	// serialize envelope
	return
	'<?xml version="1.0" encoding="'.$this->soap_defencoding .'"?'.">".
	'<SOAP-ENV:Envelope'.$ns_string.">".
	$headers.
	"<SOAP-ENV:Body>".
		$body.
	"</SOAP-ENV:Body>".
	"</SOAP-ENV:Envelope>";
    }

	/**
	 * formats a string to be inserted into an HTML stream
	 *
	 * @param string $str The string to format
	 * @return string The formatted string
	 * @access public
	 * @deprecated
	 */
    function formatDump($str){
		$str = htmlspecialchars($str);
		return nl2br($str);
    }

	/**
	* contracts (changes namespace to prefix) a qualified name
	*
	* @param    string $qname qname
	* @return	string contracted qname
	* @access   private
	*/
	function contractQname($qname){
		// get element namespace
		//$this->xdebug("Contract $qname");
		if (strrpos($qname, ':')) {
			// get unqualified name
			$name = substr($qname, strrpos($qname, ':') + 1);
			// get ns
			$ns = substr($qname, 0, strrpos($qname, ':'));
			$p = $this->getPrefixFromNamespace($ns);
			if ($p) {
				return $p . ':' . $name;
			}
			return $qname;
		} else {
			return $qname;
		}
	}

	/**
	* expands (changes prefix to namespace) a qualified name
	*
	* @param    string $qname qname
	* @return	string expanded qname
	* @access   private
	*/
	function expandQname($qname){
		// get element prefix
		if(strpos($qname,':') && !preg_match('/^http:\/\//',$qname)){
			// get unqualified name
			$name = substr(strstr($qname,':'),1);
			// get ns prefix
			$prefix = substr($qname,0,strpos($qname,':'));
			if(isset($this->namespaces[$prefix])){
				return $this->namespaces[$prefix].':'.$name;
			} else {
				return $qname;
			}
		} else {
			return $qname;
		}
	}

    /**
    * returns the local part of a prefixed string
    * returns the original string, if not prefixed
    *
    * @param string $str The prefixed string
    * @return string The local part
    * @access public
    */
	function getLocalPart($str){
		if($sstr = strrchr($str,':')){
			// get unqualified name
			return substr( $sstr, 1 );
		} else {
			return $str;
		}
	}

	/**
    * returns the prefix part of a prefixed string
    * returns false, if not prefixed
    *
    * @param string $str The prefixed string
    * @return mixed The prefix or false if there is no prefix
    * @access public
    */
	function getPrefix($str){
		if($pos = strrpos($str,':')){
			// get prefix
			return substr($str,0,$pos);
		}
		return false;
	}

	/**
    * pass it a prefix, it returns a namespace
    *
    * @param string $prefix The prefix
    * @return mixed The namespace, false if no namespace has the specified prefix
    * @access public
    */
	function getNamespaceFromPrefix($prefix){
		if (isset($this->namespaces[$prefix])) {
			return $this->namespaces[$prefix];
		}
		//$this->setError("No namespace registered for prefix '$prefix'");
		return false;
	}

	/**
    * returns the prefix for a given namespace (or prefix)
    * or false if no prefixes registered for the given namespace
    *
    * @param string $ns The namespace
    * @return mixed The prefix, false if the namespace has no prefixes
    * @access public
    */
	function getPrefixFromNamespace($ns) {
		foreach ($this->namespaces as $p => $n) {
			if ($ns == $n || $ns == $p) {
			    $this->usedNamespaces[$p] = $n;
				return $p;
			}
		}
		return false;
	}

	/**
    * returns the time in ODBC canonical form with microseconds
    *
    * @return string The time in ODBC canonical form with microseconds
    * @access public
    */
	function getmicrotime() {
		if (function_exists('gettimeofday')) {
			$tod = gettimeofday();
			$sec = $tod['sec'];
			$usec = $tod['usec'];
		} else {
			$sec = time();
			$usec = 0;
		}
		return strftime('%Y-%m-%d %H:%M:%S', $sec) . '.' . sprintf('%06d', $usec);
	}

	/**
	 * Returns a string with the output of var_dump
	 *
	 * @param mixed $data The variable to var_dump
	 * @return string The output of var_dump
	 * @access public
	 */
    function varDump($data) {
		ob_start();
		var_dump($data);
		$ret_val = ob_get_contents();
		ob_end_clean();
		return $ret_val;
	}

	/**
	* represents the object as a string
	*
	* @return	string
	* @access   public
	*/
	function __toString() {
		return $this->varDump($this);
	}
}

// XML Schema Datatype Helper Functions

//xsd:dateTime helpers

/**
* convert unix timestamp to ISO 8601 compliant date string
*
* @param    int $timestamp Unix time stamp
* @param	boolean $utc Whether the time stamp is UTC or local
* @return	mixed ISO 8601 date string or false
* @access   public
*/
function timestamp_to_iso8601($timestamp,$utc=true){
	$datestr = date('Y-m-d\TH:i:sO',$timestamp);
	$pos = strrpos($datestr, "+");
	if ($pos === FALSE) {
		$pos = strrpos($datestr, "-");
	}
	if ($pos !== FALSE) {
		if (strlen($datestr) == $pos + 5) {
			$datestr = substr($datestr, 0, $pos + 3) . ':' . substr($datestr, -2);
		}
	}
	if($utc){
		$pattern = '/'.
		'([0-9]{4})-'.	// centuries & years CCYY-
		'([0-9]{2})-'.	// months MM-
		'([0-9]{2})'.	// days DD
		'T'.			// separator T
		'([0-9]{2}):'.	// hours hh:
		'([0-9]{2}):'.	// minutes mm:
		'([0-9]{2})(\.[0-9]*)?'. // seconds ss.ss...
		'(Z|[+\-][0-9]{2}:?[0-9]{2})?'. // Z to indicate UTC, -/+HH:MM:SS.SS... for local tz's
		'/';

		if(preg_match($pattern,$datestr,$regs)){
			return sprintf('%04d-%02d-%02dT%02d:%02d:%02dZ',$regs[1],$regs[2],$regs[3],$regs[4],$regs[5],$regs[6]);
		}
		return false;
	} else {
		return $datestr;
	}
}

/**
* convert ISO 8601 compliant date string to unix timestamp
*
* @param    string $datestr ISO 8601 compliant date string
* @return	mixed Unix timestamp (int) or false
* @access   public
*/
function iso8601_to_timestamp($datestr){
	$pattern = '/'.
	'([0-9]{4})-'.	// centuries & years CCYY-
	'([0-9]{2})-'.	// months MM-
	'([0-9]{2})'.	// days DD
	'T'.			// separator T
	'([0-9]{2}):'.	// hours hh:
	'([0-9]{2}):'.	// minutes mm:
	'([0-9]{2})(\.[0-9]+)?'. // seconds ss.ss...
	'(Z|[+\-][0-9]{2}:?[0-9]{2})?'. // Z to indicate UTC, -/+HH:MM:SS.SS... for local tz's
	'/';
	if(preg_match($pattern,$datestr,$regs)){
		// not utc
		if($regs[8] != 'Z'){
			$op = substr($regs[8],0,1);
			$h = substr($regs[8],1,2);
			$m = substr($regs[8],strlen($regs[8])-2,2);
			if($op == '-'){
				$regs[4] = $regs[4] + $h;
				$regs[5] = $regs[5] + $m;
			} elseif($op == '+'){
				$regs[4] = $regs[4] - $h;
				$regs[5] = $regs[5] - $m;
			}
		}
		return gmmktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
//		return strtotime("$regs[1]-$regs[2]-$regs[3] $regs[4]:$regs[5]:$regs[6]Z");
	} else {
		return false;
	}
}

/**
* sleeps some number of microseconds
*
* @param    string $usec the number of microseconds to sleep
* @access   public
* @deprecated
*/
function usleepWindows($usec)
{
	$start = gettimeofday();
	
	do
	{
		$stop = gettimeofday();
		$timePassed = 1000000 * ($stop['sec'] - $start['sec'])
		+ $stop['usec'] - $start['usec'];
	}
	while ($timePassed < $usec);
}

?><?php



/**
* Contains information for a SOAP fault.
* Mainly used for returning faults from deployed functions
* in a server instance.
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @version  $Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $
* @access public
*/
class nusoap_fault extends nusoap_base {
	/**
	 * The fault code (client|server)
	 * @var string
	 * @access private
	 */
	var $faultcode;
	/**
	 * The fault actor
	 * @var string
	 * @access private
	 */
	var $faultactor;
	/**
	 * The fault string, a description of the fault
	 * @var string
	 * @access private
	 */
	var $faultstring;
	/**
	 * The fault detail, typically a string or array of string
	 * @var mixed
	 * @access private
	 */
	var $faultdetail;

	/**
	* constructor
    *
    * @param string $faultcode (SOAP-ENV:Client | SOAP-ENV:Server)
    * @param string $faultactor only used when msg routed between multiple actors
    * @param string $faultstring human readable error message
    * @param mixed $faultdetail detail, typically a string or array of string
	*/
	function nusoap_fault($faultcode,$faultactor='',$faultstring='',$faultdetail=''){
		parent::nusoap_base();
		$this->faultcode = $faultcode;
		$this->faultactor = $faultactor;
		$this->faultstring = $faultstring;
		$this->faultdetail = $faultdetail;
	}

	/**
	* serialize a fault
	*
	* @return	string	The serialization of the fault instance.
	* @access   public
	*/
	function serialize(){
		$ns_string = '';
		foreach($this->namespaces as $k => $v){
			$ns_string .= "\n  xmlns:$k=\"$v\"";
		}
		$return_msg =
			'<?xml version="1.0" encoding="'.$this->soap_defencoding.'"?>'.
			'<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"'.$ns_string.">\n".
				'<SOAP-ENV:Body>'.
				'<SOAP-ENV:Fault>'.
					$this->serialize_val($this->faultcode, 'faultcode').
					$this->serialize_val($this->faultactor, 'faultactor').
					$this->serialize_val($this->faultstring, 'faultstring').
					$this->serialize_val($this->faultdetail, 'detail').
				'</SOAP-ENV:Fault>'.
				'</SOAP-ENV:Body>'.
			'</SOAP-ENV:Envelope>';
		return $return_msg;
	}
}

/**
 * Backward compatibility
 */
class soap_fault extends nusoap_fault {
}

?><?php



/**
* parses an XML Schema, allows access to it's data, other utility methods.
* imperfect, no validation... yet, but quite functional.
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @author   Scott Nichol <snichol@users.sourceforge.net>
* @version  $Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $
* @access   public
*/
class nusoap_xmlschema extends nusoap_base  {
	
	// files
	var $schema = '';
	var $xml = '';
	// namespaces
	var $enclosingNamespaces;
	// schema info
	var $schemaInfo = array();
	var $schemaTargetNamespace = '';
	// types, elements, attributes defined by the schema
	var $attributes = array();
	var $complexTypes = array();
	var $complexTypeStack = array();
	var $currentComplexType = null;
	var $elements = array();
	var $elementStack = array();
	var $currentElement = null;
	var $simpleTypes = array();
	var $simpleTypeStack = array();
	var $currentSimpleType = null;
	// imports
	var $imports = array();
	// parser vars
	var $parser;
	var $position = 0;
	var $depth = 0;
	var $depth_array = array();
	var $message = array();
	var $defaultNamespace = array();
    
	/**
	* constructor
	*
	* @param    string $schema schema document URI
	* @param    string $xml xml document URI
	* @param	string $namespaces namespaces defined in enclosing XML
	* @access   public
	*/
	function nusoap_xmlschema($schema='',$xml='',$namespaces=array()){
		parent::nusoap_base();
		$this->debug('nusoap_xmlschema class instantiated, inside constructor');
		// files
		$this->schema = $schema;
		$this->xml = $xml;

		// namespaces
		$this->enclosingNamespaces = $namespaces;
		$this->namespaces = array_merge($this->namespaces, $namespaces);

		// parse schema file
		if($schema != ''){
			$this->debug('initial schema file: '.$schema);
			$this->parseFile($schema, 'schema');
		}

		// parse xml file
		if($xml != ''){
			$this->debug('initial xml file: '.$xml);
			$this->parseFile($xml, 'xml');
		}

	}

    /**
    * parse an XML file
    *
    * @param string $xml path/URL to XML file
    * @param string $type (schema | xml)
	* @return boolean
    * @access public
    */
	function parseFile($xml,$type){
		// parse xml file
		if($xml != ""){
			$xmlStr = @join("",@file($xml));
			if($xmlStr == ""){
				$msg = 'Error reading XML from '.$xml;
				$this->setError($msg);
				$this->debug($msg);
			return false;
			} else {
				$this->debug("parsing $xml");
				$this->parseString($xmlStr,$type);
				$this->debug("done parsing $xml");
			return true;
			}
		}
		return false;
	}

	/**
	* parse an XML string
	*
	* @param    string $xml path or URL
    * @param	string $type (schema|xml)
	* @access   private
	*/
	function parseString($xml,$type){
		// parse xml string
		if($xml != ""){

	    	// Create an XML parser.
	    	$this->parser = xml_parser_create();
	    	// Set the options for parsing the XML data.
	    	xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);

	    	// Set the object for the parser.
	    	xml_set_object($this->parser, $this);

	    	// Set the element handlers for the parser.
			if($type == "schema"){
		    	xml_set_element_handler($this->parser, 'schemaStartElement','schemaEndElement');
		    	xml_set_character_data_handler($this->parser,'schemaCharacterData');
			} elseif($type == "xml"){
				xml_set_element_handler($this->parser, 'xmlStartElement','xmlEndElement');
		    	xml_set_character_data_handler($this->parser,'xmlCharacterData');
			}

		    // Parse the XML file.
		    if(!xml_parse($this->parser,$xml,true)){
			// Display an error message.
				$errstr = sprintf('XML error parsing XML schema on line %d: %s',
				xml_get_current_line_number($this->parser),
				xml_error_string(xml_get_error_code($this->parser))
				);
				$this->debug($errstr);
				$this->debug("XML payload:\n" . $xml);
				$this->setError($errstr);
	    	}
            
			xml_parser_free($this->parser);
		} else{
			$this->debug('no xml passed to parseString()!!');
			$this->setError('no xml passed to parseString()!!');
		}
	}

	/**
	 * gets a type name for an unnamed type
	 *
	 * @param	string	Element name
	 * @return	string	A type name for an unnamed type
	 * @access	private
	 */
	function CreateTypeName($ename) {
		$scope = '';
		for ($i = 0; $i < count($this->complexTypeStack); $i++) {
			$scope .= $this->complexTypeStack[$i] . '_';
		}
		return $scope . $ename . '_ContainedType';
	}
	
	/**
	* start-element handler
	*
	* @param    string $parser XML parser object
	* @param    string $name element name
	* @param    string $attrs associative array of attributes
	* @access   private
	*/
	function schemaStartElement($parser, $name, $attrs) {
		
		// position in the total number of elements, starting from 0
		$pos = $this->position++;
		$depth = $this->depth++;
		// set self as current value for this depth
		$this->depth_array[$depth] = $pos;
		$this->message[$pos] = array('cdata' => ''); 
		if ($depth > 0) {
			$this->defaultNamespace[$pos] = $this->defaultNamespace[$this->depth_array[$depth - 1]];
		} else {
			$this->defaultNamespace[$pos] = false;
		}

		// get element prefix
		if($prefix = $this->getPrefix($name)){
			// get unqualified name
			$name = $this->getLocalPart($name);
		} else {
        	$prefix = '';
        }
		
        // loop thru attributes, expanding, and registering namespace declarations
        if(count($attrs) > 0){
        	foreach($attrs as $k => $v){
                // if ns declarations, add to class level array of valid namespaces
				if(preg_match('/^xmlns/',$k)){
                	//$this->xdebug("$k: $v");
                	//$this->xdebug('ns_prefix: '.$this->getPrefix($k));
                	if($ns_prefix = substr(strrchr($k,':'),1)){
                		//$this->xdebug("Add namespace[$ns_prefix] = $v");
						$this->namespaces[$ns_prefix] = $v;
					} else {
						$this->defaultNamespace[$pos] = $v;
						if (! $this->getPrefixFromNamespace($v)) {
							$this->namespaces['ns'.(count($this->namespaces)+1)] = $v;
						}
					}
					if($v == 'http://www.w3.org/2001/XMLSchema' || $v == 'http://www.w3.org/1999/XMLSchema' || $v == 'http://www.w3.org/2000/10/XMLSchema'){
						$this->XMLSchemaVersion = $v;
						$this->namespaces['xsi'] = $v.'-instance';
					}
				}
        	}
        	foreach($attrs as $k => $v){
                // expand each attribute
                $k = strpos($k,':') ? $this->expandQname($k) : $k;
                $v = strpos($v,':') ? $this->expandQname($v) : $v;
        		$eAttrs[$k] = $v;
        	}
        	$attrs = $eAttrs;
        } else {
        	$attrs = array();
        }
		// find status, register data
		switch($name){
			case 'all':			// (optional) compositor content for a complexType
			case 'choice':
			case 'group':
			case 'sequence':
				//$this->xdebug("compositor $name for currentComplexType: $this->currentComplexType and currentElement: $this->currentElement");
				$this->complexTypes[$this->currentComplexType]['compositor'] = $name;
				//if($name == 'all' || $name == 'sequence'){
				//	$this->complexTypes[$this->currentComplexType]['phpType'] = 'struct';
				//}
			break;
			case 'attribute':	// complexType attribute
            	//$this->xdebug("parsing attribute $attrs[name] $attrs[ref] of value: ".$attrs['http://schemas.xmlsoap.org/wsdl/:arrayType']);
            	$this->xdebug("parsing attribute:");
            	$this->appendDebug($this->varDump($attrs));
				if (!isset($attrs['form'])) {
					// TODO: handle globals
					$attrs['form'] = $this->schemaInfo['attributeFormDefault'];
				}
            	if (isset($attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'])) {
					$v = $attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'];
					if (!strpos($v, ':')) {
						// no namespace in arrayType attribute value...
						if ($this->defaultNamespace[$pos]) {
							// ...so use the default
							$attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'] = $this->defaultNamespace[$pos] . ':' . $attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'];
						}
					}
            	}
                if(isset($attrs['name'])){
					$this->attributes[$attrs['name']] = $attrs;
					$aname = $attrs['name'];
				} elseif(isset($attrs['ref']) && $attrs['ref'] == 'http://schemas.xmlsoap.org/soap/encoding/:arrayType'){
					if (isset($attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'])) {
	                	$aname = $attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'];
	                } else {
	                	$aname = '';
	                }
				} elseif(isset($attrs['ref'])){
					$aname = $attrs['ref'];
                    $this->attributes[$attrs['ref']] = $attrs;
				}
                
				if($this->currentComplexType){	// This should *always* be
					$this->complexTypes[$this->currentComplexType]['attrs'][$aname] = $attrs;
				}
				// arrayType attribute
				if(isset($attrs['http://schemas.xmlsoap.org/wsdl/:arrayType']) || $this->getLocalPart($aname) == 'arrayType'){
					$this->complexTypes[$this->currentComplexType]['phpType'] = 'array';
                	$prefix = $this->getPrefix($aname);
					if(isset($attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'])){
						$v = $attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'];
					} else {
						$v = '';
					}
                    if(strpos($v,'[,]')){
                        $this->complexTypes[$this->currentComplexType]['multidimensional'] = true;
                    }
                    $v = substr($v,0,strpos($v,'[')); // clip the []
                    if(!strpos($v,':') && isset($this->typemap[$this->XMLSchemaVersion][$v])){
                        $v = $this->XMLSchemaVersion.':'.$v;
                    }
                    $this->complexTypes[$this->currentComplexType]['arrayType'] = $v;
				}
			break;
			case 'complexContent':	// (optional) content for a complexType
				$this->xdebug("do nothing for element $name");
			break;
			case 'complexType':
				array_push($this->complexTypeStack, $this->currentComplexType);
				if(isset($attrs['name'])){
					// TODO: what is the scope of named complexTypes that appear
					//       nested within other c complexTypes?
					$this->xdebug('processing named complexType '.$attrs['name']);
					//$this->currentElement = false;
					$this->currentComplexType = $attrs['name'];
					$this->complexTypes[$this->currentComplexType] = $attrs;
					$this->complexTypes[$this->currentComplexType]['typeClass'] = 'complexType';
					// This is for constructs like
					//           <complexType name="ListOfString" base="soap:Array">
					//                <sequence>
					//                    <element name="string" type="xsd:string"
					//                        minOccurs="0" maxOccurs="unbounded" />
					//                </sequence>
					//            </complexType>
					if(isset($attrs['base']) && preg_match('/:Array$/',$attrs['base'])){
						$this->xdebug('complexType is unusual array');
						$this->complexTypes[$this->currentComplexType]['phpType'] = 'array';
					} else {
						$this->complexTypes[$this->currentComplexType]['phpType'] = 'struct';
					}
				} else {
					$name = $this->CreateTypeName($this->currentElement);
					$this->xdebug('processing unnamed complexType for element ' . $this->currentElement . ' named ' . $name);
					$this->currentComplexType = $name;
					//$this->currentElement = false;
					$this->complexTypes[$this->currentComplexType] = $attrs;
					$this->complexTypes[$this->currentComplexType]['typeClass'] = 'complexType';
					// This is for constructs like
					//           <complexType name="ListOfString" base="soap:Array">
					//                <sequence>
					//                    <element name="string" type="xsd:string"
					//                        minOccurs="0" maxOccurs="unbounded" />
					//                </sequence>
					//            </complexType>
					if(isset($attrs['base']) && preg_match('/:Array$/',$attrs['base'])){
						$this->xdebug('complexType is unusual array');
						$this->complexTypes[$this->currentComplexType]['phpType'] = 'array';
					} else {
						$this->complexTypes[$this->currentComplexType]['phpType'] = 'struct';
					}
				}
				$this->complexTypes[$this->currentComplexType]['simpleContent'] = 'false';
			break;
			case 'element':
				array_push($this->elementStack, $this->currentElement);
				if (!isset($attrs['form'])) {
					if ($this->currentComplexType) {
						$attrs['form'] = $this->schemaInfo['elementFormDefault'];
					} else {
						// global
						$attrs['form'] = 'qualified';
					}
				}
				if(isset($attrs['type'])){
					$this->xdebug("processing typed element ".$attrs['name']." of type ".$attrs['type']);
					if (! $this->getPrefix($attrs['type'])) {
						if ($this->defaultNamespace[$pos]) {
							$attrs['type'] = $this->defaultNamespace[$pos] . ':' . $attrs['type'];
							$this->xdebug('used default namespace to make type ' . $attrs['type']);
						}
					}
					// This is for constructs like
					//           <complexType name="ListOfString" base="soap:Array">
					//                <sequence>
					//                    <element name="string" type="xsd:string"
					//                        minOccurs="0" maxOccurs="unbounded" />
					//                </sequence>
					//            </complexType>
					if ($this->currentComplexType && $this->complexTypes[$this->currentComplexType]['phpType'] == 'array') {
						$this->xdebug('arrayType for unusual array is ' . $attrs['type']);
						$this->complexTypes[$this->currentComplexType]['arrayType'] = $attrs['type'];
					}
					$this->currentElement = $attrs['name'];
					$ename = $attrs['name'];
				} elseif(isset($attrs['ref'])){
					$this->xdebug("processing element as ref to ".$attrs['ref']);
					$this->currentElement = "ref to ".$attrs['ref'];
					$ename = $this->getLocalPart($attrs['ref']);
				} else {
					$type = $this->CreateTypeName($this->currentComplexType . '_' . $attrs['name']);
					$this->xdebug("processing untyped element " . $attrs['name'] . ' type ' . $type);
					$this->currentElement = $attrs['name'];
					$attrs['type'] = $this->schemaTargetNamespace . ':' . $type;
					$ename = $attrs['name'];
				}
				if (isset($ename) && $this->currentComplexType) {
					$this->xdebug("add element $ename to complexType $this->currentComplexType");
					$this->complexTypes[$this->currentComplexType]['elements'][$ename] = $attrs;
				} elseif (!isset($attrs['ref'])) {
					$this->xdebug("add element $ename to elements array");
					$this->elements[ $attrs['name'] ] = $attrs;
					$this->elements[ $attrs['name'] ]['typeClass'] = 'element';
				}
			break;
			case 'enumeration':	//	restriction value list member
				$this->xdebug('enumeration ' . $attrs['value']);
				if ($this->currentSimpleType) {
					$this->simpleTypes[$this->currentSimpleType]['enumeration'][] = $attrs['value'];
				} elseif ($this->currentComplexType) {
					$this->complexTypes[$this->currentComplexType]['enumeration'][] = $attrs['value'];
				}
			break;
			case 'extension':	// simpleContent or complexContent type extension
				$this->xdebug('extension ' . $attrs['base']);
				if ($this->currentComplexType) {
					$ns = $this->getPrefix($attrs['base']);
					if ($ns == '') {
						$this->complexTypes[$this->currentComplexType]['extensionBase'] = $this->schemaTargetNamespace . ':' . $attrs['base'];
					} else {
						$this->complexTypes[$this->currentComplexType]['extensionBase'] = $attrs['base'];
					}
				} else {
					$this->xdebug('no current complexType to set extensionBase');
				}
			break;
			case 'import':
			    if (isset($attrs['schemaLocation'])) {
					$this->xdebug('import namespace ' . $attrs['namespace'] . ' from ' . $attrs['schemaLocation']);
                    $this->imports[$attrs['namespace']][] = array('location' => $attrs['schemaLocation'], 'loaded' => false);
				} else {
					$this->xdebug('import namespace ' . $attrs['namespace']);
                    $this->imports[$attrs['namespace']][] = array('location' => '', 'loaded' => true);
					if (! $this->getPrefixFromNamespace($attrs['namespace'])) {
						$this->namespaces['ns'.(count($this->namespaces)+1)] = $attrs['namespace'];
					}
				}
			break;
			case 'include':
			    if (isset($attrs['schemaLocation'])) {
					$this->xdebug('include into namespace ' . $this->schemaTargetNamespace . ' from ' . $attrs['schemaLocation']);
                    $this->imports[$this->schemaTargetNamespace][] = array('location' => $attrs['schemaLocation'], 'loaded' => false);
				} else {
					$this->xdebug('ignoring invalid XML Schema construct: include without schemaLocation attribute');
				}
			break;
			case 'list':	// simpleType value list
				$this->xdebug("do nothing for element $name");
			break;
			case 'restriction':	// simpleType, simpleContent or complexContent value restriction
				$this->xdebug('restriction ' . $attrs['base']);
				if($this->currentSimpleType){
					$this->simpleTypes[$this->currentSimpleType]['type'] = $attrs['base'];
				} elseif($this->currentComplexType){
					$this->complexTypes[$this->currentComplexType]['restrictionBase'] = $attrs['base'];
					if(strstr($attrs['base'],':') == ':Array'){
						$this->complexTypes[$this->currentComplexType]['phpType'] = 'array';
					}
				}
			break;
			case 'schema':
				$this->schemaInfo = $attrs;
				$this->schemaInfo['schemaVersion'] = $this->getNamespaceFromPrefix($prefix);
				if (isset($attrs['targetNamespace'])) {
					$this->schemaTargetNamespace = $attrs['targetNamespace'];
				}
				if (!isset($attrs['elementFormDefault'])) {
					$this->schemaInfo['elementFormDefault'] = 'unqualified';
				}
				if (!isset($attrs['attributeFormDefault'])) {
					$this->schemaInfo['attributeFormDefault'] = 'unqualified';
				}
			break;
			case 'simpleContent':	// (optional) content for a complexType
				if ($this->currentComplexType) {	// This should *always* be
					$this->complexTypes[$this->currentComplexType]['simpleContent'] = 'true';
				} else {
					$this->xdebug("do nothing for element $name because there is no current complexType");
				}
			break;
			case 'simpleType':
				array_push($this->simpleTypeStack, $this->currentSimpleType);
				if(isset($attrs['name'])){
					$this->xdebug("processing simpleType for name " . $attrs['name']);
					$this->currentSimpleType = $attrs['name'];
					$this->simpleTypes[ $attrs['name'] ] = $attrs;
					$this->simpleTypes[ $attrs['name'] ]['typeClass'] = 'simpleType';
					$this->simpleTypes[ $attrs['name'] ]['phpType'] = 'scalar';
				} else {
					$name = $this->CreateTypeName($this->currentComplexType . '_' . $this->currentElement);
					$this->xdebug('processing unnamed simpleType for element ' . $this->currentElement . ' named ' . $name);
					$this->currentSimpleType = $name;
					//$this->currentElement = false;
					$this->simpleTypes[$this->currentSimpleType] = $attrs;
					$this->simpleTypes[$this->currentSimpleType]['phpType'] = 'scalar';
				}
			break;
			case 'union':	// simpleType type list
				$this->xdebug("do nothing for element $name");
			break;
			default:
				$this->xdebug("do not have any logic to process element $name");
		}
	}

	/**
	* end-element handler
	*
	* @param    string $parser XML parser object
	* @param    string $name element name
	* @access   private
	*/
	function schemaEndElement($parser, $name) {
		// bring depth down a notch
		$this->depth--;
		// position of current element is equal to the last value left in depth_array for my depth
		if(isset($this->depth_array[$this->depth])){
        	$pos = $this->depth_array[$this->depth];
        }
		// get element prefix
		if ($prefix = $this->getPrefix($name)){
			// get unqualified name
			$name = $this->getLocalPart($name);
		} else {
        	$prefix = '';
        }
		// move on...
		if($name == 'complexType'){
			$this->xdebug('done processing complexType ' . ($this->currentComplexType ? $this->currentComplexType : '(unknown)'));
			$this->xdebug($this->varDump($this->complexTypes[$this->currentComplexType]));
			$this->currentComplexType = array_pop($this->complexTypeStack);
			//$this->currentElement = false;
		}
		if($name == 'element'){
			$this->xdebug('done processing element ' . ($this->currentElement ? $this->currentElement : '(unknown)'));
			$this->currentElement = array_pop($this->elementStack);
		}
		if($name == 'simpleType'){
			$this->xdebug('done processing simpleType ' . ($this->currentSimpleType ? $this->currentSimpleType : '(unknown)'));
			$this->xdebug($this->varDump($this->simpleTypes[$this->currentSimpleType]));
			$this->currentSimpleType = array_pop($this->simpleTypeStack);
		}
	}

	/**
	* element content handler
	*
	* @param    string $parser XML parser object
	* @param    string $data element content
	* @access   private
	*/
	function schemaCharacterData($parser, $data){
		$pos = $this->depth_array[$this->depth - 1];
		$this->message[$pos]['cdata'] .= $data;
	}

	/**
	* serialize the schema
	*
	* @access   public
	*/
	function serializeSchema(){

		$schemaPrefix = $this->getPrefixFromNamespace($this->XMLSchemaVersion);
		$xml = '';
		// imports
		if (sizeof($this->imports) > 0) {
			foreach($this->imports as $ns => $list) {
				foreach ($list as $ii) {
					if ($ii['location'] != '') {
						$xml .= " <$schemaPrefix:import location=\"" . $ii['location'] . '" namespace="' . $ns . "\" />\n";
					} else {
						$xml .= " <$schemaPrefix:import namespace=\"" . $ns . "\" />\n";
					}
				}
			} 
		} 
		// complex types
		foreach($this->complexTypes as $typeName => $attrs){
			$contentStr = '';
			// serialize child elements
			if(isset($attrs['elements']) && (count($attrs['elements']) > 0)){
				foreach($attrs['elements'] as $element => $eParts){
					if(isset($eParts['ref'])){
						$contentStr .= "   <$schemaPrefix:element ref=\"$element\"/>\n";
					} else {
						$contentStr .= "   <$schemaPrefix:element name=\"$element\" type=\"" . $this->contractQName($eParts['type']) . "\"";
						foreach ($eParts as $aName => $aValue) {
							// handle, e.g., abstract, default, form, minOccurs, maxOccurs, nillable
							if ($aName != 'name' && $aName != 'type') {
								$contentStr .= " $aName=\"$aValue\"";
							}
						}
						$contentStr .= "/>\n";
					}
				}
				// compositor wraps elements
				if (isset($attrs['compositor']) && ($attrs['compositor'] != '')) {
					$contentStr = "  <$schemaPrefix:$attrs[compositor]>\n".$contentStr."  </$schemaPrefix:$attrs[compositor]>\n";
				}
			}
			// attributes
			if(isset($attrs['attrs']) && (count($attrs['attrs']) >= 1)){
				foreach($attrs['attrs'] as $attr => $aParts){
					$contentStr .= "    <$schemaPrefix:attribute";
					foreach ($aParts as $a => $v) {
						if ($a == 'ref' || $a == 'type') {
							$contentStr .= " $a=\"".$this->contractQName($v).'"';
						} elseif ($a == 'http://schemas.xmlsoap.org/wsdl/:arrayType') {
							$this->usedNamespaces['wsdl'] = $this->namespaces['wsdl'];
							$contentStr .= ' wsdl:arrayType="'.$this->contractQName($v).'"';
						} else {
							$contentStr .= " $a=\"$v\"";
						}
					}
					$contentStr .= "/>\n";
				}
			}
			// if restriction
			if (isset($attrs['restrictionBase']) && $attrs['restrictionBase'] != ''){
				$contentStr = "   <$schemaPrefix:restriction base=\"".$this->contractQName($attrs['restrictionBase'])."\">\n".$contentStr."   </$schemaPrefix:restriction>\n";
				// complex or simple content
				if ((isset($attrs['elements']) && count($attrs['elements']) > 0) || (isset($attrs['attrs']) && count($attrs['attrs']) > 0)){
					$contentStr = "  <$schemaPrefix:complexContent>\n".$contentStr."  </$schemaPrefix:complexContent>\n";
				}
			}
			// finalize complex type
			if($contentStr != ''){
				$contentStr = " <$schemaPrefix:complexType name=\"$typeName\">\n".$contentStr." </$schemaPrefix:complexType>\n";
			} else {
				$contentStr = " <$schemaPrefix:complexType name=\"$typeName\"/>\n";
			}
			$xml .= $contentStr;
		}
		// simple types
		if(isset($this->simpleTypes) && count($this->simpleTypes) > 0){
			foreach($this->simpleTypes as $typeName => $eParts){
				$xml .= " <$schemaPrefix:simpleType name=\"$typeName\">\n  <$schemaPrefix:restriction base=\"".$this->contractQName($eParts['type'])."\">\n";
				if (isset($eParts['enumeration'])) {
					foreach ($eParts['enumeration'] as $e) {
						$xml .= "  <$schemaPrefix:enumeration value=\"$e\"/>\n";
					}
				}
				$xml .= "  </$schemaPrefix:restriction>\n </$schemaPrefix:simpleType>";
			}
		}
		// elements
		if(isset($this->elements) && count($this->elements) > 0){
			foreach($this->elements as $element => $eParts){
				$xml .= " <$schemaPrefix:element name=\"$element\" type=\"".$this->contractQName($eParts['type'])."\"/>\n";
			}
		}
		// attributes
		if(isset($this->attributes) && count($this->attributes) > 0){
			foreach($this->attributes as $attr => $aParts){
				$xml .= " <$schemaPrefix:attribute name=\"$attr\" type=\"".$this->contractQName($aParts['type'])."\"\n/>";
			}
		}
		// finish 'er up
		$attr = '';
		foreach ($this->schemaInfo as $k => $v) {
			if ($k == 'elementFormDefault' || $k == 'attributeFormDefault') {
				$attr .= " $k=\"$v\"";
			}
		}
		$el = "<$schemaPrefix:schema$attr targetNamespace=\"$this->schemaTargetNamespace\"\n";
		foreach (array_diff($this->usedNamespaces, $this->enclosingNamespaces) as $nsp => $ns) {
			$el .= " xmlns:$nsp=\"$ns\"";
		}
		$xml = $el . ">\n".$xml."</$schemaPrefix:schema>\n";
		return $xml;
	}

	/**
	* adds debug data to the clas level debug string
	*
	* @param    string $string debug data
	* @access   private
	*/
	function xdebug($string){
		$this->debug('<' . $this->schemaTargetNamespace . '> '.$string);
	}

    /**
    * get the PHP type of a user defined type in the schema
    * PHP type is kind of a misnomer since it actually returns 'struct' for assoc. arrays
    * returns false if no type exists, or not w/ the given namespace
    * else returns a string that is either a native php type, or 'struct'
    *
    * @param string $type name of defined type
    * @param string $ns namespace of type
    * @return mixed
    * @access public
    * @deprecated
    */
	function getPHPType($type,$ns){
		if(isset($this->typemap[$ns][$type])){
			//print "found type '$type' and ns $ns in typemap<br>";
			return $this->typemap[$ns][$type];
		} elseif(isset($this->complexTypes[$type])){
			//print "getting type '$type' and ns $ns from complexTypes array<br>";
			return $this->complexTypes[$type]['phpType'];
		}
		return false;
	}

	/**
    * returns an associative array of information about a given type
    * returns false if no type exists by the given name
    *
	*	For a complexType typeDef = array(
	*	'restrictionBase' => '',
	*	'phpType' => '',
	*	'compositor' => '(sequence|all)',
	*	'elements' => array(), // refs to elements array
	*	'attrs' => array() // refs to attributes array
	*	... and so on (see addComplexType)
	*	)
	*
	*   For simpleType or element, the array has different keys.
    *
    * @param string $type
    * @return mixed
    * @access public
    * @see addComplexType
    * @see addSimpleType
    * @see addElement
    */
	function getTypeDef($type){
		//$this->debug("in getTypeDef for type $type");
		if (substr($type, -1) == '^') {
			$is_element = 1;
			$type = substr($type, 0, -1);
		} else {
			$is_element = 0;
		}

		if((! $is_element) && isset($this->complexTypes[$type])){
			$this->xdebug("in getTypeDef, found complexType $type");
			return $this->complexTypes[$type];
		} elseif((! $is_element) && isset($this->simpleTypes[$type])){
			$this->xdebug("in getTypeDef, found simpleType $type");
			if (!isset($this->simpleTypes[$type]['phpType'])) {
				// get info for type to tack onto the simple type
				// TODO: can this ever really apply (i.e. what is a simpleType really?)
				$uqType = substr($this->simpleTypes[$type]['type'], strrpos($this->simpleTypes[$type]['type'], ':') + 1);
				$ns = substr($this->simpleTypes[$type]['type'], 0, strrpos($this->simpleTypes[$type]['type'], ':'));
				$etype = $this->getTypeDef($uqType);
				if ($etype) {
					$this->xdebug("in getTypeDef, found type for simpleType $type:");
					$this->xdebug($this->varDump($etype));
					if (isset($etype['phpType'])) {
						$this->simpleTypes[$type]['phpType'] = $etype['phpType'];
					}
					if (isset($etype['elements'])) {
						$this->simpleTypes[$type]['elements'] = $etype['elements'];
					}
				}
			}
			return $this->simpleTypes[$type];
		} elseif(isset($this->elements[$type])){
			$this->xdebug("in getTypeDef, found element $type");
			if (!isset($this->elements[$type]['phpType'])) {
				// get info for type to tack onto the element
				$uqType = substr($this->elements[$type]['type'], strrpos($this->elements[$type]['type'], ':') + 1);
				$ns = substr($this->elements[$type]['type'], 0, strrpos($this->elements[$type]['type'], ':'));
				$etype = $this->getTypeDef($uqType);
				if ($etype) {
					$this->xdebug("in getTypeDef, found type for element $type:");
					$this->xdebug($this->varDump($etype));
					if (isset($etype['phpType'])) {
						$this->elements[$type]['phpType'] = $etype['phpType'];
					}
					if (isset($etype['elements'])) {
						$this->elements[$type]['elements'] = $etype['elements'];
					}
					if (isset($etype['extensionBase'])) {
						$this->elements[$type]['extensionBase'] = $etype['extensionBase'];
					}
				} elseif ($ns == 'http://www.w3.org/2001/XMLSchema') {
					$this->xdebug("in getTypeDef, element $type is an XSD type");
					$this->elements[$type]['phpType'] = 'scalar';
				}
			}
			return $this->elements[$type];
		} elseif(isset($this->attributes[$type])){
			$this->xdebug("in getTypeDef, found attribute $type");
			return $this->attributes[$type];
		} elseif (preg_match('/_ContainedType$/', $type)) {
			$this->xdebug("in getTypeDef, have an untyped element $type");
			$typeDef['typeClass'] = 'simpleType';
			$typeDef['phpType'] = 'scalar';
			$typeDef['type'] = 'http://www.w3.org/2001/XMLSchema:string';
			return $typeDef;
		}
		$this->xdebug("in getTypeDef, did not find $type");
		return false;
	}

	/**
    * returns a sample serialization of a given type, or false if no type by the given name
    *
    * @param string $type name of type
    * @return mixed
    * @access public
    * @deprecated
    */
    function serializeTypeDef($type){
    	//print "in sTD() for type $type<br>";
	if($typeDef = $this->getTypeDef($type)){
		$str .= '<'.$type;
	    if(is_array($typeDef['attrs'])){
		foreach($typeDef['attrs'] as $attName => $data){
		    $str .= " $attName=\"{type = ".$data['type']."}\"";
		}
	    }
	    $str .= " xmlns=\"".$this->schema['targetNamespace']."\"";
	    if(count($typeDef['elements']) > 0){
		$str .= ">";
		foreach($typeDef['elements'] as $element => $eData){
		    $str .= $this->serializeTypeDef($element);
		}
		$str .= "</$type>";
	    } elseif($typeDef['typeClass'] == 'element') {
		$str .= "></$type>";
	    } else {
		$str .= "/>";
	    }
			return $str;
	}
    	return false;
    }

    /**
    * returns HTML form elements that allow a user
    * to enter values for creating an instance of the given type.
    *
    * @param string $name name for type instance
    * @param string $type name of type
    * @return string
    * @access public
    * @deprecated
	*/
	function typeToForm($name,$type){
		// get typedef
		if($typeDef = $this->getTypeDef($type)){
			// if struct
			if($typeDef['phpType'] == 'struct'){
				$buffer .= '<table>';
				foreach($typeDef['elements'] as $child => $childDef){
					$buffer .= "
					<tr><td align='right'>$childDef[name] (type: ".$this->getLocalPart($childDef['type'])."):</td>
					<td><input type='text' name='parameters[".$name."][$childDef[name]]'></td></tr>";
				}
				$buffer .= '</table>';
			// if array
			} elseif($typeDef['phpType'] == 'array'){
				$buffer .= '<table>';
				for($i=0;$i < 3; $i++){
					$buffer .= "
					<tr><td align='right'>array item (type: $typeDef[arrayType]):</td>
					<td><input type='text' name='parameters[".$name."][]'></td></tr>";
				}
				$buffer .= '</table>';
			// if scalar
			} else {
				$buffer .= "<input type='text' name='parameters[$name]'>";
			}
		} else {
			$buffer .= "<input type='text' name='parameters[$name]'>";
		}
		return $buffer;
	}
	
	/**
	* adds a complex type to the schema
	* 
	* example: array
	* 
	* addType(
	* 	'ArrayOfstring',
	* 	'complexType',
	* 	'array',
	* 	'',
	* 	'SOAP-ENC:Array',
	* 	array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'string[]'),
	* 	'xsd:string'
	* );
	* 
	* example: PHP associative array ( SOAP Struct )
	* 
	* addType(
	* 	'SOAPStruct',
	* 	'complexType',
	* 	'struct',
	* 	'all',
	* 	array('myVar'=> array('name'=>'myVar','type'=>'string')
	* );
	* 
	* @param name
	* @param typeClass (complexType|simpleType|attribute)
	* @param phpType: currently supported are array and struct (php assoc array)
	* @param compositor (all|sequence|choice)
	* @param restrictionBase namespace:name (http://schemas.xmlsoap.org/soap/encoding/:Array)
	* @param elements = array ( name = array(name=>'',type=>'') )
	* @param attrs = array(
	* 	array(
	*		'ref' => "http://schemas.xmlsoap.org/soap/encoding/:arrayType",
	*		"http://schemas.xmlsoap.org/wsdl/:arrayType" => "string[]"
	* 	)
	* )
	* @param arrayType: namespace:name (http://www.w3.org/2001/XMLSchema:string)
	* @access public
	* @see getTypeDef
	*/
	function addComplexType($name,$typeClass='complexType',$phpType='array',$compositor='',$restrictionBase='',$elements=array(),$attrs=array(),$arrayType=''){
		$this->complexTypes[$name] = array(
	    'name'		=> $name,
	    'typeClass'	=> $typeClass,
	    'phpType'	=> $phpType,
		'compositor'=> $compositor,
	    'restrictionBase' => $restrictionBase,
		'elements'	=> $elements,
	    'attrs'		=> $attrs,
	    'arrayType'	=> $arrayType
		);
		
		$this->xdebug("addComplexType $name:");
		$this->appendDebug($this->varDump($this->complexTypes[$name]));
	}
	
	/**
	* adds a simple type to the schema
	*
	* @param string $name
	* @param string $restrictionBase namespace:name (http://schemas.xmlsoap.org/soap/encoding/:Array)
	* @param string $typeClass (should always be simpleType)
	* @param string $phpType (should always be scalar)
	* @param array $enumeration array of values
	* @access public
	* @see nusoap_xmlschema
	* @see getTypeDef
	*/
	function addSimpleType($name, $restrictionBase='', $typeClass='simpleType', $phpType='scalar', $enumeration=array()) {
		$this->simpleTypes[$name] = array(
	    'name'			=> $name,
	    'typeClass'		=> $typeClass,
	    'phpType'		=> $phpType,
	    'type'			=> $restrictionBase,
	    'enumeration'	=> $enumeration
		);
		
		$this->xdebug("addSimpleType $name:");
		$this->appendDebug($this->varDump($this->simpleTypes[$name]));
	}

	/**
	* adds an element to the schema
	*
	* @param array $attrs attributes that must include name and type
	* @see nusoap_xmlschema
	* @access public
	*/
	function addElement($attrs) {
		if (! $this->getPrefix($attrs['type'])) {
			$attrs['type'] = $this->schemaTargetNamespace . ':' . $attrs['type'];
		}
		$this->elements[ $attrs['name'] ] = $attrs;
		$this->elements[ $attrs['name'] ]['typeClass'] = 'element';
		
		$this->xdebug("addElement " . $attrs['name']);
		$this->appendDebug($this->varDump($this->elements[ $attrs['name'] ]));
	}
}

/**
 * Backward compatibility
 */
class XMLSchema extends nusoap_xmlschema {
}

?><?php



/**
* For creating serializable abstractions of native PHP types.  This class
* allows element name/namespace, XSD type, and XML attributes to be
* associated with a value.  This is extremely useful when WSDL is not
* used, but is also useful when WSDL is used with polymorphic types, including
* xsd:anyType and user-defined types.
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @version  $Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $
* @access   public
*/
class soapval extends nusoap_base {
	/**
	 * The XML element name
	 *
	 * @var string
	 * @access private
	 */
	var $name;
	/**
	 * The XML type name (string or false)
	 *
	 * @var mixed
	 * @access private
	 */
	var $type;
	/**
	 * The PHP value
	 *
	 * @var mixed
	 * @access private
	 */
	var $value;
	/**
	 * The XML element namespace (string or false)
	 *
	 * @var mixed
	 * @access private
	 */
	var $element_ns;
	/**
	 * The XML type namespace (string or false)
	 *
	 * @var mixed
	 * @access private
	 */
	var $type_ns;
	/**
	 * The XML element attributes (array or false)
	 *
	 * @var mixed
	 * @access private
	 */
	var $attributes;

	/**
	* constructor
	*
	* @param    string $name optional name
	* @param    mixed $type optional type name
	* @param	mixed $value optional value
	* @param	mixed $element_ns optional namespace of value
	* @param	mixed $type_ns optional namespace of type
	* @param	mixed $attributes associative array of attributes to add to element serialization
	* @access   public
	*/
  	function soapval($name='soapval',$type=false,$value=-1,$element_ns=false,$type_ns=false,$attributes=false) {
		parent::nusoap_base();
		$this->name = $name;
		$this->type = $type;
		$this->value = $value;
		$this->element_ns = $element_ns;
		$this->type_ns = $type_ns;
		$this->attributes = $attributes;
    }

	/**
	* return serialized value
	*
	* @param	string $use The WSDL use value (encoded|literal)
	* @return	string XML data
	* @access   public
	*/
	function serialize($use='encoded') {
		return $this->serialize_val($this->value, $this->name, $this->type, $this->element_ns, $this->type_ns, $this->attributes, $use, true);
    }

	/**
	* decodes a soapval object into a PHP native type
	*
	* @return	mixed
	* @access   public
	*/
	function decode(){
		return $this->value;
	}
}



?><?php



/**
* transport class for sending/receiving data via HTTP and HTTPS
* NOTE: PHP must be compiled with the CURL extension for HTTPS support
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @author   Scott Nichol <snichol@users.sourceforge.net>
* @version  $Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $
* @access public
*/
class soap_transport_http extends nusoap_base {

	var $url = '';
	var $uri = '';
	var $digest_uri = '';
	var $scheme = '';
	var $host = '';
	var $port = '';
	var $path = '';
	var $request_method = 'POST';
	var $protocol_version = '1.0';
	var $encoding = '';
	var $outgoing_headers = array();
	var $incoming_headers = array();
	var $incoming_cookies = array();
	var $outgoing_payload = '';
	var $incoming_payload = '';
	var $response_status_line;	// HTTP response status line
	var $useSOAPAction = true;
	var $persistentConnection = false;
	var $ch = false;	// cURL handle
	var $ch_options = array();	// cURL custom options
	var $use_curl = false;		// force cURL use
	var $proxy = null;			// proxy information (associative array)
	var $username = '';
	var $password = '';
	var $authtype = '';
	var $digestRequest = array();
	var $certRequest = array();	// keys must be cainfofile (optional), sslcertfile, sslkeyfile, passphrase, certpassword (optional), verifypeer (optional), verifyhost (optional)
								// cainfofile: certificate authority file, e.g. '$pathToPemFiles/rootca.pem'
								// sslcertfile: SSL certificate file, e.g. '$pathToPemFiles/mycert.pem'
								// sslkeyfile: SSL key file, e.g. '$pathToPemFiles/mykey.pem'
								// passphrase: SSL key password/passphrase
								// certpassword: SSL certificate password
								// verifypeer: default is 1
								// verifyhost: default is 1

	/**
	* constructor
	*
	* @param string $url The URL to which to connect
	* @param array $curl_options User-specified cURL options
	* @param boolean $use_curl Whether to try to force cURL use
	* @access public
	*/
	function soap_transport_http($url, $curl_options = NULL, $use_curl = false){
		parent::nusoap_base();
		$this->debug("ctor url=$url use_curl=$use_curl curl_options:");
		$this->appendDebug($this->varDump($curl_options));
		$this->setURL($url);
		if (is_array($curl_options)) {
			$this->ch_options = $curl_options;
		}
		$this->use_curl = $use_curl;
		preg_match('/\$Revisio' . 'n: ([^ ]+)/', $this->revision, $rev);
		$this->setHeader('User-Agent', $this->title.'/'.$this->version.' ('.$rev[1].')');
	}

	/**
	* sets a cURL option
	*
	* @param	mixed $option The cURL option (always integer?)
	* @param	mixed $value The cURL option value
	* @access   private
	*/
	function setCurlOption($option, $value) {
		$this->debug("setCurlOption option=$option, value=");
		$this->appendDebug($this->varDump($value));
		curl_setopt($this->ch, $option, $value);
	}

	/**
	* sets an HTTP header
	*
	* @param string $name The name of the header
	* @param string $value The value of the header
	* @access private
	*/
	function setHeader($name, $value) {
		$this->outgoing_headers[$name] = $value;
		$this->debug("set header $name: $value");
	}

	/**
	* unsets an HTTP header
	*
	* @param string $name The name of the header
	* @access private
	*/
	function unsetHeader($name) {
		if (isset($this->outgoing_headers[$name])) {
			$this->debug("unset header $name");
			unset($this->outgoing_headers[$name]);
		}
	}

	/**
	* sets the URL to which to connect
	*
	* @param string $url The URL to which to connect
	* @access private
	*/
	function setURL($url) {
		$this->url = $url;

		$u = parse_url($url);
		foreach($u as $k => $v){
			$this->debug("parsed URL $k = $v");
			$this->$k = $v;
		}
		
		// add any GET params to path
		if(isset($u['query']) && $u['query'] != ''){
            $this->path .= '?' . $u['query'];
		}
		
		// set default port
		if(!isset($u['port'])){
			if($u['scheme'] == 'https'){
				$this->port = 443;
			} else {
				$this->port = 80;
			}
		}
		
		$this->uri = $this->path;
		$this->digest_uri = $this->uri;
		
		// build headers
		if (!isset($u['port'])) {
			$this->setHeader('Host', $this->host);
		} else {
			$this->setHeader('Host', $this->host.':'.$this->port);
		}

		if (isset($u['user']) && $u['user'] != '') {
			$this->setCredentials(urldecode($u['user']), isset($u['pass']) ? urldecode($u['pass']) : '');
		}
	}

	/**
	* gets the I/O method to use
	*
	* @return	string	I/O method to use (socket|curl|unknown)
	* @access	private
	*/
	function io_method() {
		if ($this->use_curl || ($this->scheme == 'https') || ($this->scheme == 'http' && $this->authtype == 'ntlm') || ($this->scheme == 'http' && is_array($this->proxy) && $this->proxy['authtype'] == 'ntlm'))
			return 'curl';
		if (($this->scheme == 'http' || $this->scheme == 'ssl') && $this->authtype != 'ntlm' && (!is_array($this->proxy) || $this->proxy['authtype'] != 'ntlm'))
			return 'socket';
		return 'unknown';
	}

	/**
	* establish an HTTP connection
	*
	* @param    integer $timeout set connection timeout in seconds
	* @param	integer $response_timeout set response timeout in seconds
	* @return	boolean true if connected, false if not
	* @access   private
	*/
	function connect($connection_timeout=0,$response_timeout=30){
	  	// For PHP 4.3 with OpenSSL, change https scheme to ssl, then treat like
	  	// "regular" socket.
	  	// TODO: disabled for now because OpenSSL must be *compiled* in (not just
	  	//       loaded), and until PHP5 stream_get_wrappers is not available.
//	  	if ($this->scheme == 'https') {
//		  	if (version_compare(phpversion(), '4.3.0') >= 0) {
//		  		if (extension_loaded('openssl')) {
//		  			$this->scheme = 'ssl';
//		  			$this->debug('Using SSL over OpenSSL');
//		  		}
//		  	}
//		}
		$this->debug("connect connection_timeout $connection_timeout, response_timeout $response_timeout, scheme $this->scheme, host $this->host, port $this->port");
	  if ($this->io_method() == 'socket') {
		if (!is_array($this->proxy)) {
			$host = $this->host;
			$port = $this->port;
		} else {
			$host = $this->proxy['host'];
			$port = $this->proxy['port'];
		}

		// use persistent connection
		if($this->persistentConnection && isset($this->fp) && is_resource($this->fp)){
			if (!feof($this->fp)) {
				$this->debug('Re-use persistent connection');
				return true;
			}
			fclose($this->fp);
			$this->debug('Closed persistent connection at EOF');
		}

		// munge host if using OpenSSL
		if ($this->scheme == 'ssl') {
			$host = 'ssl://' . $host;
		}
		$this->debug('calling fsockopen with host ' . $host . ' connection_timeout ' . $connection_timeout);

		// open socket
		if($connection_timeout > 0){
			$this->fp = @fsockopen( $host, $this->port, $this->errno, $this->error_str, $connection_timeout);
		} else {
			$this->fp = @fsockopen( $host, $this->port, $this->errno, $this->error_str);
		}
		
		// test pointer
		if(!$this->fp) {
			$msg = 'Couldn\'t open socket connection to server ' . $this->url;
			if ($this->errno) {
				$msg .= ', Error ('.$this->errno.'): '.$this->error_str;
			} else {
				$msg .= ' prior to connect().  This is often a problem looking up the host name.';
			}
			$this->debug($msg);
			$this->setError($msg);
			return false;
		}
		
		// set response timeout
		$this->debug('set response timeout to ' . $response_timeout);
		socket_set_timeout( $this->fp, $response_timeout);

		$this->debug('socket connected');
		return true;
	  } else if ($this->io_method() == 'curl') {
		if (!extension_loaded('curl')) {
//			$this->setError('cURL Extension, or OpenSSL extension w/ PHP version >= 4.3 is required for HTTPS');
			$this->setError('The PHP cURL Extension is required for HTTPS or NLTM.  You will need to re-build or update your PHP to include cURL or change php.ini to load the PHP cURL extension.');
			return false;
		}
		// Avoid warnings when PHP does not have these options
		if (defined('CURLOPT_CONNECTIONTIMEOUT'))
			$CURLOPT_CONNECTIONTIMEOUT = CURLOPT_CONNECTIONTIMEOUT;
		else
			$CURLOPT_CONNECTIONTIMEOUT = 78;
		if (defined('CURLOPT_HTTPAUTH'))
			$CURLOPT_HTTPAUTH = CURLOPT_HTTPAUTH;
		else
			$CURLOPT_HTTPAUTH = 107;
		if (defined('CURLOPT_PROXYAUTH'))
			$CURLOPT_PROXYAUTH = CURLOPT_PROXYAUTH;
		else
			$CURLOPT_PROXYAUTH = 111;
		if (defined('CURLAUTH_BASIC'))
			$CURLAUTH_BASIC = CURLAUTH_BASIC;
		else
			$CURLAUTH_BASIC = 1;
		if (defined('CURLAUTH_DIGEST'))
			$CURLAUTH_DIGEST = CURLAUTH_DIGEST;
		else
			$CURLAUTH_DIGEST = 2;
		if (defined('CURLAUTH_NTLM'))
			$CURLAUTH_NTLM = CURLAUTH_NTLM;
		else
			$CURLAUTH_NTLM = 8;

		$this->debug('connect using cURL');
		// init CURL
		$this->ch = curl_init();
		// set url
		$hostURL = ($this->port != '') ? "$this->scheme://$this->host:$this->port" : "$this->scheme://$this->host";
		// add path
		$hostURL .= $this->path;
		$this->setCurlOption(CURLOPT_URL, $hostURL);
		// follow location headers (re-directs)
		if (ini_get('safe_mode') || ini_get('open_basedir')) {
			$this->debug('safe_mode or open_basedir set, so do not set CURLOPT_FOLLOWLOCATION');
			$this->debug('safe_mode = ');
			$this->appendDebug($this->varDump(ini_get('safe_mode')));
			$this->debug('open_basedir = ');
			$this->appendDebug($this->varDump(ini_get('open_basedir')));
		} else {
			$this->setCurlOption(CURLOPT_FOLLOWLOCATION, 1);
		}
		// ask for headers in the response output
		$this->setCurlOption(CURLOPT_HEADER, 1);
		// ask for the response output as the return value
		$this->setCurlOption(CURLOPT_RETURNTRANSFER, 1);
		// encode
		// We manage this ourselves through headers and encoding
//		if(function_exists('gzuncompress')){
//			$this->setCurlOption(CURLOPT_ENCODING, 'deflate');
//		}
		// persistent connection
		if ($this->persistentConnection) {
			// I believe the following comment is now bogus, having applied to
			// the code when it used CURLOPT_CUSTOMREQUEST to send the request.
			// The way we send data, we cannot use persistent connections, since
			// there will be some "junk" at the end of our request.
			//$this->setCurlOption(CURL_HTTP_VERSION_1_1, true);
			$this->persistentConnection = false;
			$this->setHeader('Connection', 'close');
		}
		// set timeouts
		if ($connection_timeout != 0) {
			$this->setCurlOption($CURLOPT_CONNECTIONTIMEOUT, $connection_timeout);
		}
		if ($response_timeout != 0) {
			$this->setCurlOption(CURLOPT_TIMEOUT, $response_timeout);
		}

		if ($this->scheme == 'https') {
			$this->debug('set cURL SSL verify options');
			// recent versions of cURL turn on peer/host checking by default,
			// while PHP binaries are not compiled with a default location for the
			// CA cert bundle, so disable peer/host checking.
			//$this->setCurlOption(CURLOPT_CAINFO, 'f:\php-4.3.2-win32\extensions\curl-ca-bundle.crt');		
			$this->setCurlOption(CURLOPT_SSL_VERIFYPEER, 0);
			$this->setCurlOption(CURLOPT_SSL_VERIFYHOST, 0);
	
			// support client certificates (thanks Tobias Boes, Doug Anarino, Eryan Ariobowo)
			if ($this->authtype == 'certificate') {
				$this->debug('set cURL certificate options');
				if (isset($this->certRequest['cainfofile'])) {
					$this->setCurlOption(CURLOPT_CAINFO, $this->certRequest['cainfofile']);
				}
				if (isset($this->certRequest['verifypeer'])) {
					$this->setCurlOption(CURLOPT_SSL_VERIFYPEER, $this->certRequest['verifypeer']);
				} else {
					$this->setCurlOption(CURLOPT_SSL_VERIFYPEER, 1);
				}
				if (isset($this->certRequest['verifyhost'])) {
					$this->setCurlOption(CURLOPT_SSL_VERIFYHOST, $this->certRequest['verifyhost']);
				} else {
					$this->setCurlOption(CURLOPT_SSL_VERIFYHOST, 1);
				}
				if (isset($this->certRequest['sslcertfile'])) {
					$this->setCurlOption(CURLOPT_SSLCERT, $this->certRequest['sslcertfile']);
				}
				if (isset($this->certRequest['sslkeyfile'])) {
					$this->setCurlOption(CURLOPT_SSLKEY, $this->certRequest['sslkeyfile']);
				}
				if (isset($this->certRequest['passphrase'])) {
					$this->setCurlOption(CURLOPT_SSLKEYPASSWD, $this->certRequest['passphrase']);
				}
				if (isset($this->certRequest['certpassword'])) {
					$this->setCurlOption(CURLOPT_SSLCERTPASSWD, $this->certRequest['certpassword']);
				}
			}
		}
		if ($this->authtype && ($this->authtype != 'certificate')) {
			if ($this->username) {
				$this->debug('set cURL username/password');
				$this->setCurlOption(CURLOPT_USERPWD, "$this->username:$this->password");
			}
			if ($this->authtype == 'basic') {
				$this->debug('set cURL for Basic authentication');
				$this->setCurlOption($CURLOPT_HTTPAUTH, $CURLAUTH_BASIC);
			}
			if ($this->authtype == 'digest') {
				$this->debug('set cURL for digest authentication');
				$this->setCurlOption($CURLOPT_HTTPAUTH, $CURLAUTH_DIGEST);
			}
			if ($this->authtype == 'ntlm') {
				$this->debug('set cURL for NTLM authentication');
				$this->setCurlOption($CURLOPT_HTTPAUTH, $CURLAUTH_NTLM);
			}
		}
		if (is_array($this->proxy)) {
			$this->debug('set cURL proxy options');
			if ($this->proxy['port'] != '') {
				$this->setCurlOption(CURLOPT_PROXY, $this->proxy['host'].':'.$this->proxy['port']);
			} else {
				$this->setCurlOption(CURLOPT_PROXY, $this->proxy['host']);
			}
			if ($this->proxy['username'] || $this->proxy['password']) {
				$this->debug('set cURL proxy authentication options');
				$this->setCurlOption(CURLOPT_PROXYUSERPWD, $this->proxy['username'].':'.$this->proxy['password']);
				if ($this->proxy['authtype'] == 'basic') {
					$this->setCurlOption($CURLOPT_PROXYAUTH, $CURLAUTH_BASIC);
				}
				if ($this->proxy['authtype'] == 'ntlm') {
					$this->setCurlOption($CURLOPT_PROXYAUTH, $CURLAUTH_NTLM);
				}
			}
		}
		$this->debug('cURL connection set up');
		return true;
	  } else {
		$this->setError('Unknown scheme ' . $this->scheme);
		$this->debug('Unknown scheme ' . $this->scheme);
		return false;
	  }
	}

	/**
	* sends the SOAP request and gets the SOAP response via HTTP[S]
	*
	* @param    string $data message data
	* @param    integer $timeout set connection timeout in seconds
	* @param	integer $response_timeout set response timeout in seconds
	* @param	array $cookies cookies to send
	* @return	string data
	* @access   public
	*/
	function send($data, $timeout=0, $response_timeout=30, $cookies=NULL) {
		
		$this->debug('entered send() with data of length: '.strlen($data));

		$this->tryagain = true;
		$tries = 0;
		while ($this->tryagain) {
			$this->tryagain = false;
			if ($tries++ < 2) {
				// make connnection
				if (!$this->connect($timeout, $response_timeout)){
					return false;
				}
				
				// send request
				if (!$this->sendRequest($data, $cookies)){
					return false;
				}
				
				// get response
				$respdata = $this->getResponse();
			} else {
				$this->setError("Too many tries to get an OK response ($this->response_status_line)");
			}
		}		
		$this->debug('end of send()');
		return $respdata;
	}


	/**
	* sends the SOAP request and gets the SOAP response via HTTPS using CURL
	*
	* @param    string $data message data
	* @param    integer $timeout set connection timeout in seconds
	* @param	integer $response_timeout set response timeout in seconds
	* @param	array $cookies cookies to send
	* @return	string data
	* @access   public
	* @deprecated
	*/
	function sendHTTPS($data, $timeout=0, $response_timeout=30, $cookies) {
		return $this->send($data, $timeout, $response_timeout, $cookies);
	}
	
	/**
	* if authenticating, set user credentials here
	*
	* @param    string $username
	* @param    string $password
	* @param	string $authtype (basic|digest|certificate|ntlm)
	* @param	array $digestRequest (keys must be nonce, nc, realm, qop)
	* @param	array $certRequest (keys must be cainfofile (optional), sslcertfile, sslkeyfile, passphrase, certpassword (optional), verifypeer (optional), verifyhost (optional): see corresponding options in cURL docs)
	* @access   public
	*/
	function setCredentials($username, $password, $authtype = 'basic', $digestRequest = array(), $certRequest = array()) {
		$this->debug("setCredentials username=$username authtype=$authtype digestRequest=");
		$this->appendDebug($this->varDump($digestRequest));
		$this->debug("certRequest=");
		$this->appendDebug($this->varDump($certRequest));
		// cf. RFC 2617
		if ($authtype == 'basic') {
			$this->setHeader('Authorization', 'Basic '.base64_encode(str_replace(':','',$username).':'.$password));
		} elseif ($authtype == 'digest') {
			if (isset($digestRequest['nonce'])) {
				$digestRequest['nc'] = isset($digestRequest['nc']) ? $digestRequest['nc']++ : 1;
				
				// calculate the Digest hashes (calculate code based on digest implementation found at: http://www.rassoc.com/gregr/weblog/stories/2002/07/09/webServicesSecurityHttpDigestAuthenticationWithoutActiveDirectory.html)
	
				// A1 = unq(username-value) ":" unq(realm-value) ":" passwd
				$A1 = $username. ':' . (isset($digestRequest['realm']) ? $digestRequest['realm'] : '') . ':' . $password;
	
				// H(A1) = MD5(A1)
				$HA1 = md5($A1);
	
				// A2 = Method ":" digest-uri-value
				$A2 = $this->request_method . ':' . $this->digest_uri;
	
				// H(A2)
				$HA2 =  md5($A2);
	
				// KD(secret, data) = H(concat(secret, ":", data))
				// if qop == auth:
				// request-digest  = <"> < KD ( H(A1),     unq(nonce-value)
				//                              ":" nc-value
				//                              ":" unq(cnonce-value)
				//                              ":" unq(qop-value)
				//                              ":" H(A2)
				//                            ) <">
				// if qop is missing,
				// request-digest  = <"> < KD ( H(A1), unq(nonce-value) ":" H(A2) ) > <">
	
				$unhashedDigest = '';
				$nonce = isset($digestRequest['nonce']) ? $digestRequest['nonce'] : '';
				$cnonce = $nonce;
				if ($digestRequest['qop'] != '') {
					$unhashedDigest = $HA1 . ':' . $nonce . ':' . sprintf("%08d", $digestRequest['nc']) . ':' . $cnonce . ':' . $digestRequest['qop'] . ':' . $HA2;
				} else {
					$unhashedDigest = $HA1 . ':' . $nonce . ':' . $HA2;
				}
	
				$hashedDigest = md5($unhashedDigest);
	
				$opaque = '';	
				if (isset($digestRequest['opaque'])) {
					$opaque = ', opaque="' . $digestRequest['opaque'] . '"';
				}

				$this->setHeader('Authorization', 'Digest username="' . $username . '", realm="' . $digestRequest['realm'] . '", nonce="' . $nonce . '", uri="' . $this->digest_uri . $opaque . '", cnonce="' . $cnonce . '", nc=' . sprintf("%08x", $digestRequest['nc']) . ', qop="' . $digestRequest['qop'] . '", response="' . $hashedDigest . '"');
			}
		} elseif ($authtype == 'certificate') {
			$this->certRequest = $certRequest;
			$this->debug('Authorization header not set for certificate');
		} elseif ($authtype == 'ntlm') {
			// do nothing
			$this->debug('Authorization header not set for ntlm');
		}
		$this->username = $username;
		$this->password = $password;
		$this->authtype = $authtype;
		$this->digestRequest = $digestRequest;
	}
	
	/**
	* set the soapaction value
	*
	* @param    string $soapaction
	* @access   public
	*/
	function setSOAPAction($soapaction) {
		$this->setHeader('SOAPAction', '"' . $soapaction . '"');
	}
	
	/**
	* use http encoding
	*
	* @param    string $enc encoding style. supported values: gzip, deflate, or both
	* @access   public
	*/
	function setEncoding($enc='gzip, deflate') {
		if (function_exists('gzdeflate')) {
			$this->protocol_version = '1.1';
			$this->setHeader('Accept-Encoding', $enc);
			if (!isset($this->outgoing_headers['Connection'])) {
				$this->setHeader('Connection', 'close');
				$this->persistentConnection = false;
			}
			// deprecated as of PHP 5.3.0
			//set_magic_quotes_runtime(0);
			$this->encoding = $enc;
		}
	}
	
	/**
	* set proxy info here
	*
	* @param    string $proxyhost use an empty string to remove proxy
	* @param    string $proxyport
	* @param	string $proxyusername
	* @param	string $proxypassword
	* @param	string $proxyauthtype (basic|ntlm)
	* @access   public
	*/
	function setProxy($proxyhost, $proxyport, $proxyusername = '', $proxypassword = '', $proxyauthtype = 'basic') {
		if ($proxyhost) {
			$this->proxy = array(
				'host' => $proxyhost,
				'port' => $proxyport,
				'username' => $proxyusername,
				'password' => $proxypassword,
				'authtype' => $proxyauthtype
			);
			if ($proxyusername != '' && $proxypassword != '' && $proxyauthtype = 'basic') {
				$this->setHeader('Proxy-Authorization', ' Basic '.base64_encode($proxyusername.':'.$proxypassword));
			}
		} else {
			$this->debug('remove proxy');
			$proxy = null;
			unsetHeader('Proxy-Authorization');
		}
	}
	

	/**
	 * Test if the given string starts with a header that is to be skipped.
	 * Skippable headers result from chunked transfer and proxy requests.
	 *
	 * @param	string $data The string to check.
	 * @returns	boolean	Whether a skippable header was found.
	 * @access	private
	 */
	function isSkippableCurlHeader(&$data) {
		$skipHeaders = array(	'HTTP/1.1 100',
								'HTTP/1.0 301',
								'HTTP/1.1 301',
								'HTTP/1.0 302',
								'HTTP/1.1 302',
								'HTTP/1.0 401',
								'HTTP/1.1 401',
								'HTTP/1.0 200 Connection established');
		foreach ($skipHeaders as $hd) {
			$prefix = substr($data, 0, strlen($hd));
			if ($prefix == $hd) return true;
		}

		return false;
	}

	/**
	* decode a string that is encoded w/ "chunked' transfer encoding
 	* as defined in RFC2068 19.4.6
	*
	* @param    string $buffer
	* @param    string $lb
	* @returns	string
	* @access   public
	* @deprecated
	*/
	function decodeChunked($buffer, $lb){
		// length := 0
		$length = 0;
		$new = '';
		
		// read chunk-size, chunk-extension (if any) and CRLF
		// get the position of the linebreak
		$chunkend = strpos($buffer, $lb);
		if ($chunkend == FALSE) {
			$this->debug('no linebreak found in decodeChunked');
			return $new;
		}
		$temp = substr($buffer,0,$chunkend);
		$chunk_size = hexdec( trim($temp) );
		$chunkstart = $chunkend + strlen($lb);
		// while (chunk-size > 0) {
		while ($chunk_size > 0) {
			$this->debug("chunkstart: $chunkstart chunk_size: $chunk_size");
			$chunkend = strpos( $buffer, $lb, $chunkstart + $chunk_size);
		  	
			// Just in case we got a broken connection
		  	if ($chunkend == FALSE) {
		  	    $chunk = substr($buffer,$chunkstart);
				// append chunk-data to entity-body
		    	$new .= $chunk;
		  	    $length += strlen($chunk);
		  	    break;
			}
			
		  	// read chunk-data and CRLF
		  	$chunk = substr($buffer,$chunkstart,$chunkend-$chunkstart);
		  	// append chunk-data to entity-body
		  	$new .= $chunk;
		  	// length := length + chunk-size
		  	$length += strlen($chunk);
		  	// read chunk-size and CRLF
		  	$chunkstart = $chunkend + strlen($lb);
			
		  	$chunkend = strpos($buffer, $lb, $chunkstart) + strlen($lb);
			if ($chunkend == FALSE) {
				break; //Just in case we got a broken connection
			}
			$temp = substr($buffer,$chunkstart,$chunkend-$chunkstart);
			$chunk_size = hexdec( trim($temp) );
			$chunkstart = $chunkend;
		}
		return $new;
	}
	
	/**
	 * Writes the payload, including HTTP headers, to $this->outgoing_payload.
	 *
	 * @param	string $data HTTP body
	 * @param	string $cookie_str data for HTTP Cookie header
	 * @return	void
	 * @access	private
	 */
	function buildPayload($data, $cookie_str = '') {
		// Note: for cURL connections, $this->outgoing_payload is ignored,
		// as is the Content-Length header, but these are still created as
		// debugging guides.

		// add content-length header
		if ($this->request_method != 'GET') {
			$this->setHeader('Content-Length', strlen($data));
		}

		// start building outgoing payload:
		if ($this->proxy) {
			$uri = $this->url;
		} else {
			$uri = $this->uri;
		}
		$req = "$this->request_method $uri HTTP/$this->protocol_version";
		$this->debug("HTTP request: $req");
		$this->outgoing_payload = "$req\r\n";

		// loop thru headers, serializing
		foreach($this->outgoing_headers as $k => $v){
			$hdr = $k.': '.$v;
			$this->debug("HTTP header: $hdr");
			$this->outgoing_payload .= "$hdr\r\n";
		}

		// add any cookies
		if ($cookie_str != '') {
			$hdr = 'Cookie: '.$cookie_str;
			$this->debug("HTTP header: $hdr");
			$this->outgoing_payload .= "$hdr\r\n";
		}

		// header/body separator
		$this->outgoing_payload .= "\r\n";
		
		// add data
		$this->outgoing_payload .= $data;
	}

	/**
	* sends the SOAP request via HTTP[S]
	*
	* @param    string $data message data
	* @param	array $cookies cookies to send
	* @return	boolean	true if OK, false if problem
	* @access   private
	*/
	function sendRequest($data, $cookies = NULL) {
		// build cookie string
		$cookie_str = $this->getCookiesForRequest($cookies, (($this->scheme == 'ssl') || ($this->scheme == 'https')));

		// build payload
		$this->buildPayload($data, $cookie_str);

	  if ($this->io_method() == 'socket') {
		// send payload
		if(!fputs($this->fp, $this->outgoing_payload, strlen($this->outgoing_payload))) {
			$this->setError('couldn\'t write message data to socket');
			$this->debug('couldn\'t write message data to socket');
			return false;
		}
		$this->debug('wrote data to socket, length = ' . strlen($this->outgoing_payload));
		return true;
	  } else if ($this->io_method() == 'curl') {
		// set payload
		// cURL does say this should only be the verb, and in fact it
		// turns out that the URI and HTTP version are appended to this, which
		// some servers refuse to work with (so we no longer use this method!)
		//$this->setCurlOption(CURLOPT_CUSTOMREQUEST, $this->outgoing_payload);
		$curl_headers = array();
		foreach($this->outgoing_headers as $k => $v){
			if ($k == 'Connection' || $k == 'Content-Length' || $k == 'Host' || $k == 'Authorization' || $k == 'Proxy-Authorization') {
				$this->debug("Skip cURL header $k: $v");
			} else {
				$curl_headers[] = "$k: $v";
			}
		}
		if ($cookie_str != '') {
			$curl_headers[] = 'Cookie: ' . $cookie_str;
		}
		$this->setCurlOption(CURLOPT_HTTPHEADER, $curl_headers);
		$this->debug('set cURL HTTP headers');
		if ($this->request_method == "POST") {
	  		$this->setCurlOption(CURLOPT_POST, 1);
	  		$this->setCurlOption(CURLOPT_POSTFIELDS, $data);
			$this->debug('set cURL POST data');
	  	} else {
	  	}
		// insert custom user-set cURL options
		foreach ($this->ch_options as $key => $val) {
			$this->setCurlOption($key, $val);
		}

		$this->debug('set cURL payload');
		return true;
	  }
	}

	/**
	* gets the SOAP response via HTTP[S]
	*
	* @return	string the response (also sets member variables like incoming_payload)
	* @access   private
	*/
	function getResponse(){
		$this->incoming_payload = '';
	    
	  if ($this->io_method() == 'socket') {
	    // loop until headers have been retrieved
	    $data = '';
	    while (!isset($lb)){

			// We might EOF during header read.
			if(feof($this->fp)) {
				$this->incoming_payload = $data;
				$this->debug('found no headers before EOF after length ' . strlen($data));
				$this->debug("received before EOF:\n" . $data);
				$this->setError('server failed to send headers');
				return false;
			}

			$tmp = fgets($this->fp, 256);
			$tmplen = strlen($tmp);
			$this->debug("read line of $tmplen bytes: " . trim($tmp));

			if ($tmplen == 0) {
				$this->incoming_payload = $data;
				$this->debug('socket read of headers timed out after length ' . strlen($data));
				$this->debug("read before timeout: " . $data);
				$this->setError('socket read of headers timed out');
				return false;
			}

			$data .= $tmp;
			$pos = strpos($data,"\r\n\r\n");
			if($pos > 1){
				$lb = "\r\n";
			} else {
				$pos = strpos($data,"\n\n");
				if($pos > 1){
					$lb = "\n";
				}
			}
			// remove 100 headers
			if (isset($lb) && preg_match('/^HTTP\/1.1 100/',$data)) {
				unset($lb);
				$data = '';
			}//
		}
		// store header data
		$this->incoming_payload .= $data;
		$this->debug('found end of headers after length ' . strlen($data));
		// process headers
		$header_data = trim(substr($data,0,$pos));
		$header_array = explode($lb,$header_data);
		$this->incoming_headers = array();
		$this->incoming_cookies = array();
		foreach($header_array as $header_line){
			$arr = explode(':',$header_line, 2);
			if(count($arr) > 1){
				$header_name = strtolower(trim($arr[0]));
				$this->incoming_headers[$header_name] = trim($arr[1]);
				if ($header_name == 'set-cookie') {
					// TODO: allow multiple cookies from parseCookie
					$cookie = $this->parseCookie(trim($arr[1]));
					if ($cookie) {
						$this->incoming_cookies[] = $cookie;
						$this->debug('found cookie: ' . $cookie['name'] . ' = ' . $cookie['value']);
					} else {
						$this->debug('did not find cookie in ' . trim($arr[1]));
					}
    			}
			} else if (isset($header_name)) {
				// append continuation line to previous header
				$this->incoming_headers[$header_name] .= $lb . ' ' . $header_line;
			}
		}
		
		// loop until msg has been received
		if (isset($this->incoming_headers['transfer-encoding']) && strtolower($this->incoming_headers['transfer-encoding']) == 'chunked') {
			$content_length =  2147483647;	// ignore any content-length header
			$chunked = true;
			$this->debug("want to read chunked content");
		} elseif (isset($this->incoming_headers['content-length'])) {
			$content_length = $this->incoming_headers['content-length'];
			$chunked = false;
			$this->debug("want to read content of length $content_length");
		} else {
			$content_length =  2147483647;
			$chunked = false;
			$this->debug("want to read content to EOF");
		}
		$data = '';
		do {
			if ($chunked) {
				$tmp = fgets($this->fp, 256);
				$tmplen = strlen($tmp);
				$this->debug("read chunk line of $tmplen bytes");
				if ($tmplen == 0) {
					$this->incoming_payload = $data;
					$this->debug('socket read of chunk length timed out after length ' . strlen($data));
					$this->debug("read before timeout:\n" . $data);
					$this->setError('socket read of chunk length timed out');
					return false;
				}
				$content_length = hexdec(trim($tmp));
				$this->debug("chunk length $content_length");
			}
			$strlen = 0;
		    while (($strlen < $content_length) && (!feof($this->fp))) {
		    	$readlen = min(8192, $content_length - $strlen);
				$tmp = fread($this->fp, $readlen);
				$tmplen = strlen($tmp);
				$this->debug("read buffer of $tmplen bytes");
				if (($tmplen == 0) && (!feof($this->fp))) {
					$this->incoming_payload = $data;
					$this->debug('socket read of body timed out after length ' . strlen($data));
					$this->debug("read before timeout:\n" . $data);
					$this->setError('socket read of body timed out');
					return false;
				}
				$strlen += $tmplen;
				$data .= $tmp;
			}
			if ($chunked && ($content_length > 0)) {
				$tmp = fgets($this->fp, 256);
				$tmplen = strlen($tmp);
				$this->debug("read chunk terminator of $tmplen bytes");
				if ($tmplen == 0) {
					$this->incoming_payload = $data;
					$this->debug('socket read of chunk terminator timed out after length ' . strlen($data));
					$this->debug("read before timeout:\n" . $data);
					$this->setError('socket read of chunk terminator timed out');
					return false;
				}
			}
		} while ($chunked && ($content_length > 0) && (!feof($this->fp)));
		if (feof($this->fp)) {
			$this->debug('read to EOF');
		}
		$this->debug('read body of length ' . strlen($data));
		$this->incoming_payload .= $data;
		$this->debug('received a total of '.strlen($this->incoming_payload).' bytes of data from server');
		
		// close filepointer
		if(
			(isset($this->incoming_headers['connection']) && strtolower($this->incoming_headers['connection']) == 'close') || 
			(! $this->persistentConnection) || feof($this->fp)){
			fclose($this->fp);
			$this->fp = false;
			$this->debug('closed socket');
		}
		
		// connection was closed unexpectedly
		if($this->incoming_payload == ''){
			$this->setError('no response from server');
			return false;
		}
		
		// decode transfer-encoding
//		if(isset($this->incoming_headers['transfer-encoding']) && strtolower($this->incoming_headers['transfer-encoding']) == 'chunked'){
//			if(!$data = $this->decodeChunked($data, $lb)){
//				$this->setError('Decoding of chunked data failed');
//				return false;
//			}
			//print "<pre>\nde-chunked:\n---------------\n$data\n\n---------------\n</pre>";
			// set decoded payload
//			$this->incoming_payload = $header_data.$lb.$lb.$data;
//		}
	
	  } else if ($this->io_method() == 'curl') {
		// send and receive
		$this->debug('send and receive with cURL');
		$this->incoming_payload = curl_exec($this->ch);
		$data = $this->incoming_payload;

        $cErr = curl_error($this->ch);
		if ($cErr != '') {
        	$err = 'cURL ERROR: '.curl_errno($this->ch).': '.$cErr.'<br>';
        	// TODO: there is a PHP bug that can cause this to SEGV for CURLINFO_CONTENT_TYPE
			foreach(curl_getinfo($this->ch) as $k => $v){
				$err .= "$k: $v<br>";
			}
			$this->debug($err);
			$this->setError($err);
			curl_close($this->ch);
	    	return false;
		} else {
			//echo '<pre>';
			//var_dump(curl_getinfo($this->ch));
			//echo '</pre>';
		}
		// close curl
		$this->debug('No cURL error, closing cURL');
		curl_close($this->ch);
		
		// try removing skippable headers
		$savedata = $data;
		while ($this->isSkippableCurlHeader($data)) {
			$this->debug("Found HTTP header to skip");
			if ($pos = strpos($data,"\r\n\r\n")) {
				$data = ltrim(substr($data,$pos));
			} elseif($pos = strpos($data,"\n\n") ) {
				$data = ltrim(substr($data,$pos));
			}
		}

		if ($data == '') {
			// have nothing left; just remove 100 header(s)
			$data = $savedata;
			while (preg_match('/^HTTP\/1.1 100/',$data)) {
				if ($pos = strpos($data,"\r\n\r\n")) {
					$data = ltrim(substr($data,$pos));
				} elseif($pos = strpos($data,"\n\n") ) {
					$data = ltrim(substr($data,$pos));
				}
			}
		}
		
		// separate content from HTTP headers
		if ($pos = strpos($data,"\r\n\r\n")) {
			$lb = "\r\n";
		} elseif( $pos = strpos($data,"\n\n")) {
			$lb = "\n";
		} else {
			$this->debug('no proper separation of headers and document');
			$this->setError('no proper separation of headers and document');
			return false;
		}
		$header_data = trim(substr($data,0,$pos));
		$header_array = explode($lb,$header_data);
		$data = ltrim(substr($data,$pos));
		$this->debug('found proper separation of headers and document');
		$this->debug('cleaned data, stringlen: '.strlen($data));
		// clean headers
		foreach ($header_array as $header_line) {
			$arr = explode(':',$header_line,2);
			if(count($arr) > 1){
				$header_name = strtolower(trim($arr[0]));
				$this->incoming_headers[$header_name] = trim($arr[1]);
				if ($header_name == 'set-cookie') {
					// TODO: allow multiple cookies from parseCookie
					$cookie = $this->parseCookie(trim($arr[1]));
					if ($cookie) {
						$this->incoming_cookies[] = $cookie;
						$this->debug('found cookie: ' . $cookie['name'] . ' = ' . $cookie['value']);
					} else {
						$this->debug('did not find cookie in ' . trim($arr[1]));
					}
    			}
			} else if (isset($header_name)) {
				// append continuation line to previous header
				$this->incoming_headers[$header_name] .= $lb . ' ' . $header_line;
			}
		}
	  }

		$this->response_status_line = $header_array[0];
		$arr = explode(' ', $this->response_status_line, 3);
		$http_version = $arr[0];
		$http_status = intval($arr[1]);
		$http_reason = count($arr) > 2 ? $arr[2] : '';

 		// see if we need to resend the request with http digest authentication
 		if (isset($this->incoming_headers['location']) && ($http_status == 301 || $http_status == 302)) {
 			$this->debug("Got $http_status $http_reason with Location: " . $this->incoming_headers['location']);
 			$this->setURL($this->incoming_headers['location']);
			$this->tryagain = true;
			return false;
		}

 		// see if we need to resend the request with http digest authentication
 		if (isset($this->incoming_headers['www-authenticate']) && $http_status == 401) {
 			$this->debug("Got 401 $http_reason with WWW-Authenticate: " . $this->incoming_headers['www-authenticate']);
 			if (strstr($this->incoming_headers['www-authenticate'], "Digest ")) {
 				$this->debug('Server wants digest authentication');
 				// remove "Digest " from our elements
 				$digestString = str_replace('Digest ', '', $this->incoming_headers['www-authenticate']);
 				
 				// parse elements into array
 				$digestElements = explode(',', $digestString);
 				foreach ($digestElements as $val) {
 					$tempElement = explode('=', trim($val), 2);
 					$digestRequest[$tempElement[0]] = str_replace("\"", '', $tempElement[1]);
 				}

				// should have (at least) qop, realm, nonce
 				if (isset($digestRequest['nonce'])) {
 					$this->setCredentials($this->username, $this->password, 'digest', $digestRequest);
 					$this->tryagain = true;
 					return false;
 				}
 			}
			$this->debug('HTTP authentication failed');
			$this->setError('HTTP authentication failed');
			return false;
 		}
		
		if (
			($http_status >= 300 && $http_status <= 307) ||
			($http_status >= 400 && $http_status <= 417) ||
			($http_status >= 501 && $http_status <= 505)
		   ) {
			$this->setError("Unsupported HTTP response status $http_status $http_reason (soapclient->response has contents of the response)");
			return false;
		}

		// decode content-encoding
		if(isset($this->incoming_headers['content-encoding']) && $this->incoming_headers['content-encoding'] != ''){
			if(strtolower($this->incoming_headers['content-encoding']) == 'deflate' || strtolower($this->incoming_headers['content-encoding']) == 'gzip'){
    			// if decoding works, use it. else assume data wasn't gzencoded
    			if(function_exists('gzinflate')){
					//$timer->setMarker('starting decoding of gzip/deflated content');
					// IIS 5 requires gzinflate instead of gzuncompress (similar to IE 5 and gzdeflate v. gzcompress)
					// this means there are no Zlib headers, although there should be
					$this->debug('The gzinflate function exists');
					$datalen = strlen($data);
					if ($this->incoming_headers['content-encoding'] == 'deflate') {
						if ($degzdata = @gzinflate($data)) {
	    					$data = $degzdata;
	    					$this->debug('The payload has been inflated to ' . strlen($data) . ' bytes');
	    					if (strlen($data) < $datalen) {
	    						// test for the case that the payload has been compressed twice
		    					$this->debug('The inflated payload is smaller than the gzipped one; try again');
								if ($degzdata = @gzinflate($data)) {
			    					$data = $degzdata;
			    					$this->debug('The payload has been inflated again to ' . strlen($data) . ' bytes');
								}
	    					}
	    				} else {
	    					$this->debug('Error using gzinflate to inflate the payload');
	    					$this->setError('Error using gzinflate to inflate the payload');
	    				}
					} elseif ($this->incoming_headers['content-encoding'] == 'gzip') {
						if ($degzdata = @gzinflate(substr($data, 10))) {	// do our best
							$data = $degzdata;
	    					$this->debug('The payload has been un-gzipped to ' . strlen($data) . ' bytes');
	    					if (strlen($data) < $datalen) {
	    						// test for the case that the payload has been compressed twice
		    					$this->debug('The un-gzipped payload is smaller than the gzipped one; try again');
								if ($degzdata = @gzinflate(substr($data, 10))) {
			    					$data = $degzdata;
			    					$this->debug('The payload has been un-gzipped again to ' . strlen($data) . ' bytes');
								}
	    					}
	    				} else {
	    					$this->debug('Error using gzinflate to un-gzip the payload');
							$this->setError('Error using gzinflate to un-gzip the payload');
	    				}
					}
					//$timer->setMarker('finished decoding of gzip/deflated content');
					//print "<xmp>\nde-inflated:\n---------------\n$data\n-------------\n</xmp>";
					// set decoded payload
					$this->incoming_payload = $header_data.$lb.$lb.$data;
    			} else {
					$this->debug('The server sent compressed data. Your php install must have the Zlib extension compiled in to support this.');
					$this->setError('The server sent compressed data. Your php install must have the Zlib extension compiled in to support this.');
				}
			} else {
				$this->debug('Unsupported Content-Encoding ' . $this->incoming_headers['content-encoding']);
				$this->setError('Unsupported Content-Encoding ' . $this->incoming_headers['content-encoding']);
			}
		} else {
			$this->debug('No Content-Encoding header');
		}
		
		if(strlen($data) == 0){
			$this->debug('no data after headers!');
			$this->setError('no data present after HTTP headers');
			return false;
		}
		
		return $data;
	}

	/**
	 * sets the content-type for the SOAP message to be sent
	 *
	 * @param	string $type the content type, MIME style
	 * @param	mixed $charset character set used for encoding (or false)
	 * @access	public
	 */
	function setContentType($type, $charset = false) {
		$this->setHeader('Content-Type', $type . ($charset ? '; charset=' . $charset : ''));
	}

	/**
	 * specifies that an HTTP persistent connection should be used
	 *
	 * @return	boolean whether the request was honored by this method.
	 * @access	public
	 */
	function usePersistentConnection(){
		if (isset($this->outgoing_headers['Accept-Encoding'])) {
			return false;
		}
		$this->protocol_version = '1.1';
		$this->persistentConnection = true;
		$this->setHeader('Connection', 'Keep-Alive');
		return true;
	}

	/**
	 * parse an incoming Cookie into it's parts
	 *
	 * @param	string $cookie_str content of cookie
	 * @return	array with data of that cookie
	 * @access	private
	 */
	/*
	 * TODO: allow a Set-Cookie string to be parsed into multiple cookies
	 */
	function parseCookie($cookie_str) {
		$cookie_str = str_replace('; ', ';', $cookie_str) . ';';
		$data = preg_split('/;/', $cookie_str);
		$value_str = $data[0];

		$cookie_param = 'domain=';
		$start = strpos($cookie_str, $cookie_param);
		if ($start > 0) {
			$domain = substr($cookie_str, $start + strlen($cookie_param));
			$domain = substr($domain, 0, strpos($domain, ';'));
		} else {
			$domain = '';
		}

		$cookie_param = 'expires=';
		$start = strpos($cookie_str, $cookie_param);
		if ($start > 0) {
			$expires = substr($cookie_str, $start + strlen($cookie_param));
			$expires = substr($expires, 0, strpos($expires, ';'));
		} else {
			$expires = '';
		}

		$cookie_param = 'path=';
		$start = strpos($cookie_str, $cookie_param);
		if ( $start > 0 ) {
			$path = substr($cookie_str, $start + strlen($cookie_param));
			$path = substr($path, 0, strpos($path, ';'));
		} else {
			$path = '/';
		}
						
		$cookie_param = ';secure;';
		if (strpos($cookie_str, $cookie_param) !== FALSE) {
			$secure = true;
		} else {
			$secure = false;
		}

		$sep_pos = strpos($value_str, '=');

		if ($sep_pos) {
			$name = substr($value_str, 0, $sep_pos);
			$value = substr($value_str, $sep_pos + 1);
			$cookie= array(	'name' => $name,
			                'value' => $value,
							'domain' => $domain,
							'path' => $path,
							'expires' => $expires,
							'secure' => $secure
							);		
			return $cookie;
		}
		return false;
	}
  
	/**
	 * sort out cookies for the current request
	 *
	 * @param	array $cookies array with all cookies
	 * @param	boolean $secure is the send-content secure or not?
	 * @return	string for Cookie-HTTP-Header
	 * @access	private
	 */
	function getCookiesForRequest($cookies, $secure=false) {
		$cookie_str = '';
		if ((! is_null($cookies)) && (is_array($cookies))) {
			foreach ($cookies as $cookie) {
				if (! is_array($cookie)) {
					continue;
				}
	    		$this->debug("check cookie for validity: ".$cookie['name'].'='.$cookie['value']);
				if ((isset($cookie['expires'])) && (! empty($cookie['expires']))) {
					if (strtotime($cookie['expires']) <= time()) {
						$this->debug('cookie has expired');
						continue;
					}
				}
				if ((isset($cookie['domain'])) && (! empty($cookie['domain']))) {
					$domain = preg_quote($cookie['domain']);
					if (! preg_match("'.*$domain$'i", $this->host)) {
						$this->debug('cookie has different domain');
						continue;
					}
				}
				if ((isset($cookie['path'])) && (! empty($cookie['path']))) {
					$path = preg_quote($cookie['path']);
					if (! preg_match("'^$path.*'i", $this->path)) {
						$this->debug('cookie is for a different path');
						continue;
					}
				}
				if ((! $secure) && (isset($cookie['secure'])) && ($cookie['secure'])) {
					$this->debug('cookie is secure, transport is not');
					continue;
				}
				$cookie_str .= $cookie['name'] . '=' . $cookie['value'] . '; ';
	    		$this->debug('add cookie to Cookie-String: ' . $cookie['name'] . '=' . $cookie['value']);
			}
		}
		return $cookie_str;
  }
}

?><?php



/**
*
* nusoap_server allows the user to create a SOAP server
* that is capable of receiving messages and returning responses
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @author   Scott Nichol <snichol@users.sourceforge.net>
* @version  $Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $
* @access   public
*/
class nusoap_server extends nusoap_base {
	/**
	 * HTTP headers of request
	 * @var array
	 * @access private
	 */
	var $headers = array();
	/**
	 * HTTP request
	 * @var string
	 * @access private
	 */
	var $request = '';
	/**
	 * SOAP headers from request (incomplete namespace resolution; special characters not escaped) (text)
	 * @var string
	 * @access public
	 */
	var $requestHeaders = '';
	/**
	 * SOAP Headers from request (parsed)
	 * @var mixed
	 * @access public
	 */
	var $requestHeader = NULL;
	/**
	 * SOAP body request portion (incomplete namespace resolution; special characters not escaped) (text)
	 * @var string
	 * @access public
	 */
	var $document = '';
	/**
	 * SOAP payload for request (text)
	 * @var string
	 * @access public
	 */
	var $requestSOAP = '';
	/**
	 * requested method namespace URI
	 * @var string
	 * @access private
	 */
	var $methodURI = '';
	/**
	 * name of method requested
	 * @var string
	 * @access private
	 */
	var $methodname = '';
	/**
	 * method parameters from request
	 * @var array
	 * @access private
	 */
	var $methodparams = array();
	/**
	 * SOAP Action from request
	 * @var string
	 * @access private
	 */
	var $SOAPAction = '';
	/**
	 * character set encoding of incoming (request) messages
	 * @var string
	 * @access public
	 */
	var $xml_encoding = '';
	/**
	 * toggles whether the parser decodes element content w/ utf8_decode()
	 * @var boolean
	 * @access public
	 */
    var $decode_utf8 = true;

	/**
	 * HTTP headers of response
	 * @var array
	 * @access public
	 */
	var $outgoing_headers = array();
	/**
	 * HTTP response
	 * @var string
	 * @access private
	 */
	var $response = '';
	/**
	 * SOAP headers for response (text or array of soapval or associative array)
	 * @var mixed
	 * @access public
	 */
	var $responseHeaders = '';
	/**
	 * SOAP payload for response (text)
	 * @var string
	 * @access private
	 */
	var $responseSOAP = '';
	/**
	 * method return value to place in response
	 * @var mixed
	 * @access private
	 */
	var $methodreturn = false;
	/**
	 * whether $methodreturn is a string of literal XML
	 * @var boolean
	 * @access public
	 */
	var $methodreturnisliteralxml = false;
	/**
	 * SOAP fault for response (or false)
	 * @var mixed
	 * @access private
	 */
	var $fault = false;
	/**
	 * text indication of result (for debugging)
	 * @var string
	 * @access private
	 */
	var $result = 'successful';

	/**
	 * assoc array of operations => opData; operations are added by the register()
	 * method or by parsing an external WSDL definition
	 * @var array
	 * @access private
	 */
	var $operations = array();
	/**
	 * wsdl instance (if one)
	 * @var mixed
	 * @access private
	 */
	var $wsdl = false;
	/**
	 * URL for WSDL (if one)
	 * @var mixed
	 * @access private
	 */
	var $externalWSDLURL = false;
	/**
	 * whether to append debug to response as XML comment
	 * @var boolean
	 * @access public
	 */
	var $debug_flag = false;


	/**
	* constructor
    * the optional parameter is a path to a WSDL file that you'd like to bind the server instance to.
	*
    * @param mixed $wsdl file path or URL (string), or wsdl instance (object)
	* @access   public
	*/
	function nusoap_server($wsdl=false){
		parent::nusoap_base();
		// turn on debugging?
		global $debug;
		global $HTTP_SERVER_VARS;

		if (isset($_SERVER)) {
			$this->debug("_SERVER is defined:");
			$this->appendDebug($this->varDump($_SERVER));
		} elseif (isset($HTTP_SERVER_VARS)) {
			$this->debug("HTTP_SERVER_VARS is defined:");
			$this->appendDebug($this->varDump($HTTP_SERVER_VARS));
		} else {
			$this->debug("Neither _SERVER nor HTTP_SERVER_VARS is defined.");
		}

		if (isset($debug)) {
			$this->debug("In nusoap_server, set debug_flag=$debug based on global flag");
			$this->debug_flag = $debug;
		} elseif (isset($_SERVER['QUERY_STRING'])) {
			$qs = explode('&', $_SERVER['QUERY_STRING']);
			foreach ($qs as $v) {
				if (substr($v, 0, 6) == 'debug=') {
					$this->debug("In nusoap_server, set debug_flag=" . substr($v, 6) . " based on query string #1");
					$this->debug_flag = substr($v, 6);
				}
			}
		} elseif (isset($HTTP_SERVER_VARS['QUERY_STRING'])) {
			$qs = explode('&', $HTTP_SERVER_VARS['QUERY_STRING']);
			foreach ($qs as $v) {
				if (substr($v, 0, 6) == 'debug=') {
					$this->debug("In nusoap_server, set debug_flag=" . substr($v, 6) . " based on query string #2");
					$this->debug_flag = substr($v, 6);
				}
			}
		}

		// wsdl
		if($wsdl){
			$this->debug("In nusoap_server, WSDL is specified");
			if (is_object($wsdl) && (get_class($wsdl) == 'wsdl')) {
				$this->wsdl = $wsdl;
				$this->externalWSDLURL = $this->wsdl->wsdl;
				$this->debug('Use existing wsdl instance from ' . $this->externalWSDLURL);
			} else {
				$this->debug('Create wsdl from ' . $wsdl);
				$this->wsdl = new wsdl($wsdl);
				$this->externalWSDLURL = $wsdl;
			}
			$this->appendDebug($this->wsdl->getDebug());
			$this->wsdl->clearDebug();
			if($err = $this->wsdl->getError()){
				die('WSDL ERROR: '.$err);
			}
		}
	}

	/**
	* processes request and returns response
	*
	* @param    string $data usually is the value of $HTTP_RAW_POST_DATA
	* @access   public
	*/
	function service($data){
		global $HTTP_SERVER_VARS;

		if (isset($_SERVER['REQUEST_METHOD'])) {
			$rm = $_SERVER['REQUEST_METHOD'];
		} elseif (isset($HTTP_SERVER_VARS['REQUEST_METHOD'])) {
			$rm = $HTTP_SERVER_VARS['REQUEST_METHOD'];
		} else {
			$rm = '';
		}

		if (isset($_SERVER['QUERY_STRING'])) {
			$qs = $_SERVER['QUERY_STRING'];
		} elseif (isset($HTTP_SERVER_VARS['QUERY_STRING'])) {
			$qs = $HTTP_SERVER_VARS['QUERY_STRING'];
		} else {
			$qs = '';
		}
		$this->debug("In service, request method=$rm query string=$qs strlen(\$data)=" . strlen($data));

		if ($rm == 'POST') {
			$this->debug("In service, invoke the request");
			$this->parse_request($data);
			if (! $this->fault) {
				$this->invoke_method();
			}
			if (! $this->fault) {
				$this->serialize_return();
			}
			$this->send_response();
		} elseif (preg_match('/wsdl/', $qs) ){
			$this->debug("In service, this is a request for WSDL");
			if ($this->externalWSDLURL){
              if (strpos($this->externalWSDLURL, "http://") !== false) { // assume URL
				$this->debug("In service, re-direct for WSDL");
				header('Location: '.$this->externalWSDLURL);
              } else { // assume file
				$this->debug("In service, use file passthru for WSDL");
                header("Content-Type: text/xml\r\n");
				$pos = strpos($this->externalWSDLURL, "file://");
				if ($pos === false) {
					$filename = $this->externalWSDLURL;
				} else {
					$filename = substr($this->externalWSDLURL, $pos + 7);
				}
                $fp = fopen($this->externalWSDLURL, 'r');
                fpassthru($fp);
              }
			} elseif ($this->wsdl) {
				$this->debug("In service, serialize WSDL");
				header("Content-Type: text/xml; charset=ISO-8859-1\r\n");
				print $this->wsdl->serialize($this->debug_flag);
				if ($this->debug_flag) {
					$this->debug('wsdl:');
					$this->appendDebug($this->varDump($this->wsdl));
					print $this->getDebugAsXMLComment();
				}
			} else {
				$this->debug("In service, there is no WSDL");
				header("Content-Type: text/html; charset=ISO-8859-1\r\n");
				print "This service does not provide WSDL";
			}
		} elseif ($this->wsdl) {
			$this->debug("In service, return Web description");
			print $this->wsdl->webDescription();
		} else {
			$this->debug("In service, no Web description");
			header("Content-Type: text/html; charset=ISO-8859-1\r\n");
			print "This service does not provide a Web description";
		}
	}

	/**
	* parses HTTP request headers.
	*
	* The following fields are set by this function (when successful)
	*
	* headers
	* request
	* xml_encoding
	* SOAPAction
	*
	* @access   private
	*/
	function parse_http_headers() {
		global $HTTP_SERVER_VARS;

		$this->request = '';
		$this->SOAPAction = '';
		if(function_exists('getallheaders')){
			$this->debug("In parse_http_headers, use getallheaders");
			$headers = getallheaders();
			foreach($headers as $k=>$v){
				$k = strtolower($k);
				$this->headers[$k] = $v;
				$this->request .= "$k: $v\r\n";
				$this->debug("$k: $v");
			}
			// get SOAPAction header
			if(isset($this->headers['soapaction'])){
				$this->SOAPAction = str_replace('"','',$this->headers['soapaction']);
			}
			// get the character encoding of the incoming request
			if(isset($this->headers['content-type']) && strpos($this->headers['content-type'],'=')){
				$enc = str_replace('"','',substr(strstr($this->headers["content-type"],'='),1));
				if(preg_match('/^(ISO-8859-1|US-ASCII|UTF-8)$/i',$enc)){
					$this->xml_encoding = strtoupper($enc);
				} else {
					$this->xml_encoding = 'US-ASCII';
				}
			} else {
				// should be US-ASCII for HTTP 1.0 or ISO-8859-1 for HTTP 1.1
				$this->xml_encoding = 'ISO-8859-1';
			}
		} elseif(isset($_SERVER) && is_array($_SERVER)){
			$this->debug("In parse_http_headers, use _SERVER");
			foreach ($_SERVER as $k => $v) {
				if (substr($k, 0, 5) == 'HTTP_') {
					$k = str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($k, 5))));
				} else {
					$k = str_replace(' ', '-', strtolower(str_replace('_', ' ', $k)));
				}
				if ($k == 'soapaction') {
					// get SOAPAction header
					$k = 'SOAPAction';
					$v = str_replace('"', '', $v);
					$v = str_replace('\\', '', $v);
					$this->SOAPAction = $v;
				} else if ($k == 'content-type') {
					// get the character encoding of the incoming request
					if (strpos($v, '=')) {
						$enc = substr(strstr($v, '='), 1);
						$enc = str_replace('"', '', $enc);
						$enc = str_replace('\\', '', $enc);
						if (preg_match('/^(ISO-8859-1|US-ASCII|UTF-8)$/i',$enc)) {
							$this->xml_encoding = strtoupper($enc);
						} else {
							$this->xml_encoding = 'US-ASCII';
						}
					} else {
						// should be US-ASCII for HTTP 1.0 or ISO-8859-1 for HTTP 1.1
						$this->xml_encoding = 'ISO-8859-1';
					}
				}
				$this->headers[$k] = $v;
				$this->request .= "$k: $v\r\n";
				$this->debug("$k: $v");
			}
		} elseif (is_array($HTTP_SERVER_VARS)) {
			$this->debug("In parse_http_headers, use HTTP_SERVER_VARS");
			foreach ($HTTP_SERVER_VARS as $k => $v) {
				if (substr($k, 0, 5) == 'HTTP_') {
					$k = str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($k, 5)))); 	                                         $k = strtolower(substr($k, 5));
				} else {
					$k = str_replace(' ', '-', strtolower(str_replace('_', ' ', $k))); 	                                         $k = strtolower($k);
				}
				if ($k == 'soapaction') {
					// get SOAPAction header
					$k = 'SOAPAction';
					$v = str_replace('"', '', $v);
					$v = str_replace('\\', '', $v);
					$this->SOAPAction = $v;
				} else if ($k == 'content-type') {
					// get the character encoding of the incoming request
					if (strpos($v, '=')) {
						$enc = substr(strstr($v, '='), 1);
						$enc = str_replace('"', '', $enc);
						$enc = str_replace('\\', '', $enc);
						if (preg_match('/^(ISO-8859-1|US-ASCII|UTF-8)$/i',$enc)) {
							$this->xml_encoding = strtoupper($enc);
						} else {
							$this->xml_encoding = 'US-ASCII';
						}
					} else {
						// should be US-ASCII for HTTP 1.0 or ISO-8859-1 for HTTP 1.1
						$this->xml_encoding = 'ISO-8859-1';
					}
				}
				$this->headers[$k] = $v;
				$this->request .= "$k: $v\r\n";
				$this->debug("$k: $v");
			}
		} else {
			$this->debug("In parse_http_headers, HTTP headers not accessible");
			$this->setError("HTTP headers not accessible");
		}
	}

	/**
	* parses a request
	*
	* The following fields are set by this function (when successful)
	*
	* headers
	* request
	* xml_encoding
	* SOAPAction
	* request
	* requestSOAP
	* methodURI
	* methodname
	* methodparams
	* requestHeaders
	* document
	*
	* This sets the fault field on error
	*
	* @param    string $data XML string
	* @access   private
	*/
	function parse_request($data='') {
		$this->debug('entering parse_request()');
		$this->parse_http_headers();
		$this->debug('got character encoding: '.$this->xml_encoding);
		// uncompress if necessary
		if (isset($this->headers['content-encoding']) && $this->headers['content-encoding'] != '') {
			$this->debug('got content encoding: ' . $this->headers['content-encoding']);
			if ($this->headers['content-encoding'] == 'deflate' || $this->headers['content-encoding'] == 'gzip') {
		    	// if decoding works, use it. else assume data wasn't gzencoded
				if (function_exists('gzuncompress')) {
					if ($this->headers['content-encoding'] == 'deflate' && $degzdata = @gzuncompress($data)) {
						$data = $degzdata;
					} elseif ($this->headers['content-encoding'] == 'gzip' && $degzdata = gzinflate(substr($data, 10))) {
						$data = $degzdata;
					} else {
						$this->fault('SOAP-ENV:Client', 'Errors occurred when trying to decode the data');
						return;
					}
				} else {
					$this->fault('SOAP-ENV:Client', 'This Server does not support compressed data');
					return;
				}
			}
		}
		$this->request .= "\r\n".$data;
		$data = $this->parseRequest($this->headers, $data);
		$this->requestSOAP = $data;
		$this->debug('leaving parse_request');
	}

	/**
	* invokes a PHP function for the requested SOAP method
	*
	* The following fields are set by this function (when successful)
	*
	* methodreturn
	*
	* Note that the PHP function that is called may also set the following
	* fields to affect the response sent to the client
	*
	* responseHeaders
	* outgoing_headers
	*
	* This sets the fault field on error
	*
	* @access   private
	*/
	function invoke_method() {
		$this->debug('in invoke_method, methodname=' . $this->methodname . ' methodURI=' . $this->methodURI . ' SOAPAction=' . $this->SOAPAction);

		//
		// if you are debugging in this area of the code, your service uses a class to implement methods,
		// you use SOAP RPC, and the client is .NET, please be aware of the following...
		// when the .NET wsdl.exe utility generates a proxy, it will remove the '.' or '..' from the
		// method name.  that is fine for naming the .NET methods.  it is not fine for properly constructing
		// the XML request and reading the XML response.  you need to add the RequestElementName and
		// ResponseElementName to the System.Web.Services.Protocols.SoapRpcMethodAttribute that wsdl.exe
		// generates for the method.  these parameters are used to specify the correct XML element names
		// for .NET to use, i.e. the names with the '.' in them.
		//
		$orig_methodname = $this->methodname;
		if ($this->wsdl) {
			if ($this->opData = $this->wsdl->getOperationData($this->methodname)) {
				$this->debug('in invoke_method, found WSDL operation=' . $this->methodname);
				$this->appendDebug('opData=' . $this->varDump($this->opData));
			} elseif ($this->opData = $this->wsdl->getOperationDataForSoapAction($this->SOAPAction)) {
				// Note: hopefully this case will only be used for doc/lit, since rpc services should have wrapper element
				$this->debug('in invoke_method, found WSDL soapAction=' . $this->SOAPAction . ' for operation=' . $this->opData['name']);
				$this->appendDebug('opData=' . $this->varDump($this->opData));
				$this->methodname = $this->opData['name'];
			} else {
				$this->debug('in invoke_method, no WSDL for operation=' . $this->methodname);
				$this->fault('SOAP-ENV:Client', "Operation '" . $this->methodname . "' is not defined in the WSDL for this service");
				return;
			}
		} else {
			$this->debug('in invoke_method, no WSDL to validate method');
		}

		// if a . is present in $this->methodname, we see if there is a class in scope,
		// which could be referred to. We will also distinguish between two deliminators,
		// to allow methods to be called a the class or an instance
		if (strpos($this->methodname, '..') > 0) {
			$delim = '..';
		} else if (strpos($this->methodname, '.') > 0) {
			$delim = '.';
		} else {
			$delim = '';
		}
		$this->debug("in invoke_method, delim=$delim");

		$class = '';
		$method = '';
		if (strlen($delim) > 0 && substr_count($this->methodname, $delim) == 1) {
			$try_class = substr($this->methodname, 0, strpos($this->methodname, $delim));
			if (class_exists($try_class)) {
				// get the class and method name
				$class = $try_class;
				$method = substr($this->methodname, strpos($this->methodname, $delim) + strlen($delim));
				$this->debug("in invoke_method, class=$class method=$method delim=$delim");
			} else {
				$this->debug("in invoke_method, class=$try_class not found");
			}
		} else {
			$try_class = '';
			$this->debug("in invoke_method, no class to try");
		}

		// does method exist?
		if ($class == '') {
			if (!function_exists($this->methodname)) {
				$this->debug("in invoke_method, function '$this->methodname' not found!");
				$this->result = 'fault: method not found';
				$this->fault('SOAP-ENV:Client',"method '$this->methodname'('$orig_methodname') not defined in service('$try_class' '$delim')");
				return;
			}
		} else {
			$method_to_compare = (substr(phpversion(), 0, 2) == '4.') ? strtolower($method) : $method;
			if (!in_array($method_to_compare, get_class_methods($class))) {
				$this->debug("in invoke_method, method '$this->methodname' not found in class '$class'!");
				$this->result = 'fault: method not found';
				$this->fault('SOAP-ENV:Client',"method '$this->methodname'/'$method_to_compare'('$orig_methodname') not defined in service/'$class'('$try_class' '$delim')");
				return;
			}
		}

		// evaluate message, getting back parameters
		// verify that request parameters match the method's signature
		if(! $this->verify_method($this->methodname,$this->methodparams)){
			// debug
			$this->debug('ERROR: request not verified against method signature');
			$this->result = 'fault: request failed validation against method signature';
			// return fault
			$this->fault('SOAP-ENV:Client',"Operation '$this->methodname' not defined in service.");
			return;
		}

		// if there are parameters to pass
		$this->debug('in invoke_method, params:');
		$this->appendDebug($this->varDump($this->methodparams));
		$this->debug("in invoke_method, calling '$this->methodname'");
		if (!function_exists('call_user_func_array')) {
			if ($class == '') {
				$this->debug('in invoke_method, calling function using eval()');
				$funcCall = "\$this->methodreturn = $this->methodname(";
			} else {
				if ($delim == '..') {
					$this->debug('in invoke_method, calling class method using eval()');
					$funcCall = "\$this->methodreturn = ".$class."::".$method."(";
				} else {
					$this->debug('in invoke_method, calling instance method using eval()');
					// generate unique instance name
					$instname = "\$inst_".time();
					$funcCall = $instname." = new ".$class."(); ";
					$funcCall .= "\$this->methodreturn = ".$instname."->".$method."(";
				}
			}
			if ($this->methodparams) {
				foreach ($this->methodparams as $param) {
					if (is_array($param) || is_object($param)) {
						$this->fault('SOAP-ENV:Client', 'NuSOAP does not handle complexType parameters correctly when using eval; call_user_func_array must be available');
						return;
					}
					$funcCall .= "\"$param\",";
				}
				$funcCall = substr($funcCall, 0, -1);
			}
			$funcCall .= ');';
			$this->debug('in invoke_method, function call: '.$funcCall);
			@eval($funcCall);
		} else {
			if ($class == '') {
				$this->debug('in invoke_method, calling function using call_user_func_array()');
				$call_arg = "$this->methodname";	// straight assignment changes $this->methodname to lower case after call_user_func_array()
			} elseif ($delim == '..') {
				$this->debug('in invoke_method, calling class method using call_user_func_array()');
				$call_arg = array ($class, $method);
			} else {
				$this->debug('in invoke_method, calling instance method using call_user_func_array()');
				$instance = new $class ();
				$call_arg = array(&$instance, $method);
			}
			if (is_array($this->methodparams)) {
				$this->methodreturn = call_user_func_array($call_arg, array_values($this->methodparams));
			} else {
				$this->methodreturn = call_user_func_array($call_arg, array());
			}
		}
        $this->debug('in invoke_method, methodreturn:');
        $this->appendDebug($this->varDump($this->methodreturn));
		$this->debug("in invoke_method, called method $this->methodname, received data of type ".gettype($this->methodreturn));
	}

	/**
	* serializes the return value from a PHP function into a full SOAP Envelope
	*
	* The following fields are set by this function (when successful)
	*
	* responseSOAP
	*
	* This sets the fault field on error
	*
	* @access   private
	*/
	function serialize_return() {
		$this->debug('Entering serialize_return methodname: ' . $this->methodname . ' methodURI: ' . $this->methodURI);
		// if fault
		if (isset($this->methodreturn) && is_object($this->methodreturn) && ((get_class($this->methodreturn) == 'soap_fault') || (get_class($this->methodreturn) == 'nusoap_fault'))) {
			$this->debug('got a fault object from method');
			$this->fault = $this->methodreturn;
			return;
		} elseif ($this->methodreturnisliteralxml) {
			$return_val = $this->methodreturn;
		// returned value(s)
		} else {
			$this->debug('got a(n) '.gettype($this->methodreturn).' from method');
			$this->debug('serializing return value');
			if($this->wsdl){
				if (sizeof($this->opData['output']['parts']) > 1) {
					$this->debug('more than one output part, so use the method return unchanged');
			    	$opParams = $this->methodreturn;
			    } elseif (sizeof($this->opData['output']['parts']) == 1) {
					$this->debug('exactly one output part, so wrap the method return in a simple array');
					// TODO: verify that it is not already wrapped!
			    	//foreach ($this->opData['output']['parts'] as $name => $type) {
					//	$this->debug('wrap in element named ' . $name);
			    	//}
			    	$opParams = array($this->methodreturn);
			    }
			    $return_val = $this->wsdl->serializeRPCParameters($this->methodname,'output',$opParams);
			    $this->appendDebug($this->wsdl->getDebug());
			    $this->wsdl->clearDebug();
				if($errstr = $this->wsdl->getError()){
					$this->debug('got wsdl error: '.$errstr);
					$this->fault('SOAP-ENV:Server', 'unable to serialize result');
					return;
				}
			} else {
				if (isset($this->methodreturn)) {
					$return_val = $this->serialize_val($this->methodreturn, 'return');
				} else {
					$return_val = '';
					$this->debug('in absence of WSDL, assume void return for backward compatibility');
				}
			}
		}
		$this->debug('return value:');
		$this->appendDebug($this->varDump($return_val));

		$this->debug('serializing response');
		if ($this->wsdl) {
			$this->debug('have WSDL for serialization: style is ' . $this->opData['style']);
			if ($this->opData['style'] == 'rpc') {
				$this->debug('style is rpc for serialization: use is ' . $this->opData['output']['use']);
				if ($this->opData['output']['use'] == 'literal') {
					// http://www.ws-i.org/Profiles/BasicProfile-1.1-2004-08-24.html R2735 says rpc/literal accessor elements should not be in a namespace
					if ($this->methodURI) {
						$payload = '<ns1:'.$this->methodname.'Response xmlns:ns1="'.$this->methodURI.'">'.$return_val.'</ns1:'.$this->methodname."Response>";
					} else {
						$payload = '<'.$this->methodname.'Response>'.$return_val.'</'.$this->methodname.'Response>';
					}
				} else {
					if ($this->methodURI) {
						$payload = '<ns1:'.$this->methodname.'Response xmlns:ns1="'.$this->methodURI.'">'.$return_val.'</ns1:'.$this->methodname."Response>";
					} else {
						$payload = '<'.$this->methodname.'Response>'.$return_val.'</'.$this->methodname.'Response>';
					}
				}
			} else {
				$this->debug('style is not rpc for serialization: assume document');
				$payload = $return_val;
			}
		} else {
			$this->debug('do not have WSDL for serialization: assume rpc/encoded');
			$payload = '<ns1:'.$this->methodname.'Response xmlns:ns1="'.$this->methodURI.'">'.$return_val.'</ns1:'.$this->methodname."Response>";
		}
		$this->result = 'successful';
		if($this->wsdl){
			//if($this->debug_flag){
            	$this->appendDebug($this->wsdl->getDebug());
            //	}
			if (isset($this->opData['output']['encodingStyle'])) {
				$encodingStyle = $this->opData['output']['encodingStyle'];
			} else {
				$encodingStyle = '';
			}
			// Added: In case we use a WSDL, return a serialized env. WITH the usedNamespaces.
			$this->responseSOAP = $this->serializeEnvelope($payload,$this->responseHeaders,$this->wsdl->usedNamespaces,$this->opData['style'],$this->opData['output']['use'],$encodingStyle);
		} else {
			$this->responseSOAP = $this->serializeEnvelope($payload,$this->responseHeaders);
		}
		$this->debug("Leaving serialize_return");
	}

	/**
	* sends an HTTP response
	*
	* The following fields are set by this function (when successful)
	*
	* outgoing_headers
	* response
	*
	* @access   private
	*/
	function send_response() {
		$this->debug('Enter send_response');
		if ($this->fault) {
			$payload = $this->fault->serialize();
			$this->outgoing_headers[] = "HTTP/1.0 500 Internal Server Error";
			$this->outgoing_headers[] = "Status: 500 Internal Server Error";
		} else {
			$payload = $this->responseSOAP;
			// Some combinations of PHP+Web server allow the Status
			// to come through as a header.  Since OK is the default
			// just do nothing.
			// $this->outgoing_headers[] = "HTTP/1.0 200 OK";
			// $this->outgoing_headers[] = "Status: 200 OK";
		}
        // add debug data if in debug mode
		if(isset($this->debug_flag) && $this->debug_flag){
        	$payload .= $this->getDebugAsXMLComment();
        }
		$this->outgoing_headers[] = "Server: $this->title Server v$this->version";
		preg_match('/\$Revisio' . 'n: ([^ ]+)/', $this->revision, $rev);
		$this->outgoing_headers[] = "X-SOAP-Server: $this->title/$this->version (".$rev[1].")";
		// Let the Web server decide about this
		//$this->outgoing_headers[] = "Connection: Close\r\n";
		$payload = $this->getHTTPBody($payload);
		$type = $this->getHTTPContentType();
		$charset = $this->getHTTPContentTypeCharset();
		$this->outgoing_headers[] = "Content-Type: $type" . ($charset ? '; charset=' . $charset : '');
		//begin code to compress payload - by John
		// NOTE: there is no way to know whether the Web server will also compress
		// this data.
		if (strlen($payload) > 1024 && isset($this->headers) && isset($this->headers['accept-encoding'])) {	
			if (strstr($this->headers['accept-encoding'], 'gzip')) {
				if (function_exists('gzencode')) {
					if (isset($this->debug_flag) && $this->debug_flag) {
						$payload .= "<!-- Content being gzipped -->";
					}
					$this->outgoing_headers[] = "Content-Encoding: gzip";
					$payload = gzencode($payload);
				} else {
					if (isset($this->debug_flag) && $this->debug_flag) {
						$payload .= "<!-- Content will not be gzipped: no gzencode -->";
					}
				}
			} elseif (strstr($this->headers['accept-encoding'], 'deflate')) {
				// Note: MSIE requires gzdeflate output (no Zlib header and checksum),
				// instead of gzcompress output,
				// which conflicts with HTTP 1.1 spec (http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html#sec3.5)
				if (function_exists('gzdeflate')) {
					if (isset($this->debug_flag) && $this->debug_flag) {
						$payload .= "<!-- Content being deflated -->";
					}
					$this->outgoing_headers[] = "Content-Encoding: deflate";
					$payload = gzdeflate($payload);
				} else {
					if (isset($this->debug_flag) && $this->debug_flag) {
						$payload .= "<!-- Content will not be deflated: no gzcompress -->";
					}
				}
			}
		}
		//end code
		$this->outgoing_headers[] = "Content-Length: ".strlen($payload);
		reset($this->outgoing_headers);
		foreach($this->outgoing_headers as $hdr){
			header($hdr, false);
		}
		print $payload;
		$this->response = join("\r\n",$this->outgoing_headers)."\r\n\r\n".$payload;
	}

	/**
	* takes the value that was created by parsing the request
	* and compares to the method's signature, if available.
	*
	* @param	string	$operation	The operation to be invoked
	* @param	array	$request	The array of parameter values
	* @return	boolean	Whether the operation was found
	* @access   private
	*/
	function verify_method($operation,$request){
		if(isset($this->wsdl) && is_object($this->wsdl)){
			if($this->wsdl->getOperationData($operation)){
				return true;
			}
	    } elseif(isset($this->operations[$operation])){
			return true;
		}
		return false;
	}

	/**
	* processes SOAP message received from client
	*
	* @param	array	$headers	The HTTP headers
	* @param	string	$data		unprocessed request data from client
	* @return	mixed	value of the message, decoded into a PHP type
	* @access   private
	*/
    function parseRequest($headers, $data) {
		$this->debug('Entering parseRequest() for data of length ' . strlen($data) . ' headers:');
		$this->appendDebug($this->varDump($headers));
    	if (!isset($headers['content-type'])) {
			$this->setError('Request not of type text/xml (no content-type header)');
			return false;
    	}
		if (!strstr($headers['content-type'], 'text/xml')) {
			$this->setError('Request not of type text/xml');
			return false;
		}
		if (strpos($headers['content-type'], '=')) {
			$enc = str_replace('"', '', substr(strstr($headers["content-type"], '='), 1));
			$this->debug('Got response encoding: ' . $enc);
			if(preg_match('/^(ISO-8859-1|US-ASCII|UTF-8)$/i',$enc)){
				$this->xml_encoding = strtoupper($enc);
			} else {
				$this->xml_encoding = 'US-ASCII';
			}
		} else {
			// should be US-ASCII for HTTP 1.0 or ISO-8859-1 for HTTP 1.1
			$this->xml_encoding = 'ISO-8859-1';
		}
		$this->debug('Use encoding: ' . $this->xml_encoding . ' when creating nusoap_parser');
		// parse response, get soap parser obj
		$parser = new nusoap_parser($data,$this->xml_encoding,'',$this->decode_utf8);
		// parser debug
		$this->debug("parser debug: \n".$parser->getDebug());
		// if fault occurred during message parsing
		if($err = $parser->getError()){
			$this->result = 'fault: error in msg parsing: '.$err;
			$this->fault('SOAP-ENV:Client',"error in msg parsing:\n".$err);
		// else successfully parsed request into soapval object
		} else {
			// get/set methodname
			$this->methodURI = $parser->root_struct_namespace;
			$this->methodname = $parser->root_struct_name;
			$this->debug('methodname: '.$this->methodname.' methodURI: '.$this->methodURI);
			$this->debug('calling parser->get_soapbody()');
			$this->methodparams = $parser->get_soapbody();
			// get SOAP headers
			$this->requestHeaders = $parser->getHeaders();
			// get SOAP Header
			$this->requestHeader = $parser->get_soapheader();
            // add document for doclit support
            $this->document = $parser->document;
		}
	 }

	/**
	* gets the HTTP body for the current response.
	*
	* @param string $soapmsg The SOAP payload
	* @return string The HTTP body, which includes the SOAP payload
	* @access private
	*/
	function getHTTPBody($soapmsg) {
		return $soapmsg;
	}
	
	/**
	* gets the HTTP content type for the current response.
	*
	* Note: getHTTPBody must be called before this.
	*
	* @return string the HTTP content type for the current response.
	* @access private
	*/
	function getHTTPContentType() {
		return 'text/xml';
	}
	
	/**
	* gets the HTTP content type charset for the current response.
	* returns false for non-text content types.
	*
	* Note: getHTTPBody must be called before this.
	*
	* @return string the HTTP content type charset for the current response.
	* @access private
	*/
	function getHTTPContentTypeCharset() {
		return $this->soap_defencoding;
	}

	/**
	* add a method to the dispatch map (this has been replaced by the register method)
	*
	* @param    string $methodname
	* @param    string $in array of input values
	* @param    string $out array of output values
	* @access   public
	* @deprecated
	*/
	function add_to_map($methodname,$in,$out){
			$this->operations[$methodname] = array('name' => $methodname,'in' => $in,'out' => $out);
	}

	/**
	* register a service function with the server
	*
	* @param    string $name the name of the PHP function, class.method or class..method
	* @param    array $in assoc array of input values: key = param name, value = param type
	* @param    array $out assoc array of output values: key = param name, value = param type
	* @param	mixed $namespace the element namespace for the method or false
	* @param	mixed $soapaction the soapaction for the method or false
	* @param	mixed $style optional (rpc|document) or false Note: when 'document' is specified, parameter and return wrappers are created for you automatically
	* @param	mixed $use optional (encoded|literal) or false
	* @param	string $documentation optional Description to include in WSDL
	* @param	string $encodingStyle optional (usually 'http://schemas.xmlsoap.org/soap/encoding/' for encoded)
	* @access   public
	*/
	function register($name,$in=array(),$out=array(),$namespace=false,$soapaction=false,$style=false,$use=false,$documentation='',$encodingStyle=''){
		global $HTTP_SERVER_VARS;

		if($this->externalWSDLURL){
			die('You cannot bind to an external WSDL file, and register methods outside of it! Please choose either WSDL or no WSDL.');
		}
		if (! $name) {
			die('You must specify a name when you register an operation');
		}
		if (!is_array($in)) {
			die('You must provide an array for operation inputs');
		}
		if (!is_array($out)) {
			die('You must provide an array for operation outputs');
		}
		if(false == $namespace) {
		}
		if(false == $soapaction) {
			if (isset($_SERVER)) {
				$SERVER_NAME = $_SERVER['SERVER_NAME'];
				$SCRIPT_NAME = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
				$HTTPS = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : (isset($HTTP_SERVER_VARS['HTTPS']) ? $HTTP_SERVER_VARS['HTTPS'] : 'off');
			} elseif (isset($HTTP_SERVER_VARS)) {
				$SERVER_NAME = $HTTP_SERVER_VARS['SERVER_NAME'];
				$SCRIPT_NAME = isset($HTTP_SERVER_VARS['PHP_SELF']) ? $HTTP_SERVER_VARS['PHP_SELF'] : $HTTP_SERVER_VARS['SCRIPT_NAME'];
				$HTTPS = isset($HTTP_SERVER_VARS['HTTPS']) ? $HTTP_SERVER_VARS['HTTPS'] : 'off';
			} else {
				$this->setError("Neither _SERVER nor HTTP_SERVER_VARS is available");
			}
        	if ($HTTPS == '1' || $HTTPS == 'on') {
        		$SCHEME = 'https';
        	} else {
        		$SCHEME = 'http';
        	}
			$soapaction = "$SCHEME://$SERVER_NAME$SCRIPT_NAME/$name";
		}
		if(false == $style) {
			$style = "rpc";
		}
		if(false == $use) {
			$use = "encoded";
		}
		if ($use == 'encoded' && $encodingStyle == '') {
			$encodingStyle = 'http://schemas.xmlsoap.org/soap/encoding/';
		}

		$this->operations[$name] = array(
	    'name' => $name,
	    'in' => $in,
	    'out' => $out,
	    'namespace' => $namespace,
	    'soapaction' => $soapaction,
	    'style' => $style);
        if($this->wsdl){
        	$this->wsdl->addOperation($name,$in,$out,$namespace,$soapaction,$style,$use,$documentation,$encodingStyle);
	    }
		return true;
	}

	/**
	* Specify a fault to be returned to the client.
	* This also acts as a flag to the server that a fault has occured.
	*
	* @param	string $faultcode
	* @param	string $faultstring
	* @param	string $faultactor
	* @param	string $faultdetail
	* @access   public
	*/
	function fault($faultcode,$faultstring,$faultactor='',$faultdetail=''){
		if ($faultdetail == '' && $this->debug_flag) {
			$faultdetail = $this->getDebug();
		}
		$this->fault = new nusoap_fault($faultcode,$faultactor,$faultstring,$faultdetail);
		$this->fault->soap_defencoding = $this->soap_defencoding;
	}

    /**
    * Sets up wsdl object.
    * Acts as a flag to enable internal WSDL generation
    *
    * @param string $serviceName, name of the service
    * @param mixed $namespace optional 'tns' service namespace or false
    * @param mixed $endpoint optional URL of service endpoint or false
    * @param string $style optional (rpc|document) WSDL style (also specified by operation)
    * @param string $transport optional SOAP transport
    * @param mixed $schemaTargetNamespace optional 'types' targetNamespace for service schema or false
    */
    function configureWSDL($serviceName,$namespace = false,$endpoint = false,$style='rpc', $transport = 'http://schemas.xmlsoap.org/soap/http', $schemaTargetNamespace = false)
    {
    	global $HTTP_SERVER_VARS;

		if (isset($_SERVER)) {
			$SERVER_NAME = $_SERVER['SERVER_NAME'];
			$SERVER_PORT = $_SERVER['SERVER_PORT'];
			$SCRIPT_NAME = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
			$HTTPS = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : (isset($HTTP_SERVER_VARS['HTTPS']) ? $HTTP_SERVER_VARS['HTTPS'] : 'off');
		} elseif (isset($HTTP_SERVER_VARS)) {
			$SERVER_NAME = $HTTP_SERVER_VARS['SERVER_NAME'];
			$SERVER_PORT = $HTTP_SERVER_VARS['SERVER_PORT'];
			$SCRIPT_NAME = isset($HTTP_SERVER_VARS['PHP_SELF']) ? $HTTP_SERVER_VARS['PHP_SELF'] : $HTTP_SERVER_VARS['SCRIPT_NAME'];
			$HTTPS = isset($HTTP_SERVER_VARS['HTTPS']) ? $HTTP_SERVER_VARS['HTTPS'] : 'off';
		} else {
			$this->setError("Neither _SERVER nor HTTP_SERVER_VARS is available");
		}
		// If server name has port number attached then strip it (else port number gets duplicated in WSDL output) (occurred using lighttpd and FastCGI)
		$colon = strpos($SERVER_NAME,":");
		if ($colon) {
		    $SERVER_NAME = substr($SERVER_NAME, 0, $colon);
		}
		if ($SERVER_PORT == 80) {
			$SERVER_PORT = '';
		} else {
			$SERVER_PORT = ':' . $SERVER_PORT;
		}
        if(false == $namespace) {
            $namespace = "http://$SERVER_NAME/soap/$serviceName";
        }
        
        if(false == $endpoint) {
        	if ($HTTPS == '1' || $HTTPS == 'on') {
        		$SCHEME = 'https';
        	} else {
        		$SCHEME = 'http';
        	}
            $endpoint = "$SCHEME://$SERVER_NAME$SERVER_PORT$SCRIPT_NAME";
        }
        
        if(false == $schemaTargetNamespace) {
            $schemaTargetNamespace = $namespace;
        }
        
		$this->wsdl = new wsdl;
		$this->wsdl->serviceName = $serviceName;
        $this->wsdl->endpoint = $endpoint;
		$this->wsdl->namespaces['tns'] = $namespace;
		$this->wsdl->namespaces['soap'] = 'http://schemas.xmlsoap.org/wsdl/soap/';
		$this->wsdl->namespaces['wsdl'] = 'http://schemas.xmlsoap.org/wsdl/';
		if ($schemaTargetNamespace != $namespace) {
			$this->wsdl->namespaces['types'] = $schemaTargetNamespace;
		}
        $this->wsdl->schemas[$schemaTargetNamespace][0] = new nusoap_xmlschema('', '', $this->wsdl->namespaces);
        if ($style == 'document') {
	        $this->wsdl->schemas[$schemaTargetNamespace][0]->schemaInfo['elementFormDefault'] = 'qualified';
        }
        $this->wsdl->schemas[$schemaTargetNamespace][0]->schemaTargetNamespace = $schemaTargetNamespace;
        $this->wsdl->schemas[$schemaTargetNamespace][0]->imports['http://schemas.xmlsoap.org/soap/encoding/'][0] = array('location' => '', 'loaded' => true);
        $this->wsdl->schemas[$schemaTargetNamespace][0]->imports['http://schemas.xmlsoap.org/wsdl/'][0] = array('location' => '', 'loaded' => true);
        $this->wsdl->bindings[$serviceName.'Binding'] = array(
        	'name'=>$serviceName.'Binding',
            'style'=>$style,
            'transport'=>$transport,
            'portType'=>$serviceName.'PortType');
        $this->wsdl->ports[$serviceName.'Port'] = array(
        	'binding'=>$serviceName.'Binding',
            'location'=>$endpoint,
            'bindingType'=>'http://schemas.xmlsoap.org/wsdl/soap/');
    }
}

/**
 * Backward compatibility
 */
class soap_server extends nusoap_server {
}

?><?php



/**
* parses a WSDL file, allows access to it's data, other utility methods.
* also builds WSDL structures programmatically.
* 
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @author   Scott Nichol <snichol@users.sourceforge.net>
* @version  $Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $
* @access public 
*/
class wsdl extends nusoap_base {
	// URL or filename of the root of this WSDL
    var $wsdl; 
    // define internal arrays of bindings, ports, operations, messages, etc.
    var $schemas = array();
    var $currentSchema;
    var $message = array();
    var $complexTypes = array();
    var $messages = array();
    var $currentMessage;
    var $currentOperation;
    var $portTypes = array();
    var $currentPortType;
    var $bindings = array();
    var $currentBinding;
    var $ports = array();
    var $currentPort;
    var $opData = array();
    var $status = '';
    var $documentation = false;
    var $endpoint = ''; 
    // array of wsdl docs to import
    var $import = array(); 
    // parser vars
    var $parser;
    var $position = 0;
    var $depth = 0;
    var $depth_array = array();
	// for getting wsdl
	var $proxyhost = '';
    var $proxyport = '';
	var $proxyusername = '';
	var $proxypassword = '';
	var $timeout = 0;
	var $response_timeout = 30;
	var $curl_options = array();	// User-specified cURL options
	var $use_curl = false;			// whether to always try to use cURL
	// for HTTP authentication
	var $username = '';				// Username for HTTP authentication
	var $password = '';				// Password for HTTP authentication
	var $authtype = '';				// Type of HTTP authentication
	var $certRequest = array();		// Certificate for HTTP SSL authentication

    /**
     * constructor
     * 
     * @param string $wsdl WSDL document URL
	 * @param string $proxyhost
	 * @param string $proxyport
	 * @param string $proxyusername
	 * @param string $proxypassword
	 * @param integer $timeout set the connection timeout
	 * @param integer $response_timeout set the response timeout
	 * @param array $curl_options user-specified cURL options
	 * @param boolean $use_curl try to use cURL
     * @access public 
     */
    function wsdl($wsdl = '',$proxyhost=false,$proxyport=false,$proxyusername=false,$proxypassword=false,$timeout=0,$response_timeout=30,$curl_options=null,$use_curl=false){
		parent::nusoap_base();
		$this->debug("ctor wsdl=$wsdl timeout=$timeout response_timeout=$response_timeout");
        $this->proxyhost = $proxyhost;
        $this->proxyport = $proxyport;
		$this->proxyusername = $proxyusername;
		$this->proxypassword = $proxypassword;
		$this->timeout = $timeout;
		$this->response_timeout = $response_timeout;
		if (is_array($curl_options))
			$this->curl_options = $curl_options;
		$this->use_curl = $use_curl;
		$this->fetchWSDL($wsdl);
    }

	/**
	 * fetches the WSDL document and parses it
	 *
	 * @access public
	 */
	function fetchWSDL($wsdl) {
		$this->debug("parse and process WSDL path=$wsdl");
		$this->wsdl = $wsdl;
        // parse wsdl file
        if ($this->wsdl != "") {
            $this->parseWSDL($this->wsdl);
        }
        // imports
        // TODO: handle imports more properly, grabbing them in-line and nesting them
    	$imported_urls = array();
    	$imported = 1;
    	while ($imported > 0) {
    		$imported = 0;
    		// Schema imports
    		foreach ($this->schemas as $ns => $list) {
    			foreach ($list as $xs) {
					$wsdlparts = parse_url($this->wsdl);	// this is bogusly simple!
		            foreach ($xs->imports as $ns2 => $list2) {
		                for ($ii = 0; $ii < count($list2); $ii++) {
		                	if (! $list2[$ii]['loaded']) {
		                		$this->schemas[$ns]->imports[$ns2][$ii]['loaded'] = true;
		                		$url = $list2[$ii]['location'];
								if ($url != '') {
									$urlparts = parse_url($url);
									if (!isset($urlparts['host'])) {
										$url = $wsdlparts['scheme'] . '://' . $wsdlparts['host'] . (isset($wsdlparts['port']) ? ':' .$wsdlparts['port'] : '') .
												substr($wsdlparts['path'],0,strrpos($wsdlparts['path'],'/') + 1) .$urlparts['path'];
									}
									if (! in_array($url, $imported_urls)) {
					                	$this->parseWSDL($url);
				                		$imported++;
				                		$imported_urls[] = $url;
				                	}
								} else {
									$this->debug("Unexpected scenario: empty URL for unloaded import");
								}
							}
						}
		            } 
    			}
    		}
    		// WSDL imports
			$wsdlparts = parse_url($this->wsdl);	// this is bogusly simple!
            foreach ($this->import as $ns => $list) {
                for ($ii = 0; $ii < count($list); $ii++) {
                	if (! $list[$ii]['loaded']) {
                		$this->import[$ns][$ii]['loaded'] = true;
                		$url = $list[$ii]['location'];
						if ($url != '') {
							$urlparts = parse_url($url);
							if (!isset($urlparts['host'])) {
								$url = $wsdlparts['scheme'] . '://' . $wsdlparts['host'] . (isset($wsdlparts['port']) ? ':' . $wsdlparts['port'] : '') .
										substr($wsdlparts['path'],0,strrpos($wsdlparts['path'],'/') + 1) .$urlparts['path'];
							}
							if (! in_array($url, $imported_urls)) {
			                	$this->parseWSDL($url);
		                		$imported++;
		                		$imported_urls[] = $url;
		                	}
						} else {
							$this->debug("Unexpected scenario: empty URL for unloaded import");
						}
					}
				}
            } 
		}
        // add new data to operation data
        foreach($this->bindings as $binding => $bindingData) {
            if (isset($bindingData['operations']) && is_array($bindingData['operations'])) {
                foreach($bindingData['operations'] as $operation => $da