<?php

namespace talus;

use talus\internal\E_OK;
include_once __DIR__ . '/impl/spack_inner.php';

/**
 * spack header + spack body<p>
 * you can get array object or <b>spack packed</b> string buffer from here.</p>
 */
class SpackProto {
	function __construct()
	{
		$this->parser = new internal\PacketParser();
	}
	
	/**
	 * the version of spack protocol, only if this is set in HTTP Header, it can be parsed.
	 *
	 * @return <p> spack version
	 * </p>
	 */
	static function version()
	{
		return internal\VERSION_MAJOR . "." . internal\VERSION_MINOR;
	}

	function clear()
	{
		$this->parser->clear();
	}
	
	function get_arrobj()
	{
		return $this->parser->bodyobj();
	}

	function get_buf()
	{
		return $this->parser->headbuf() . $this->parser->bodybuf();
	}

	function get_sequence_id()
	{
		return $this->parser->sequence_id();
	}
	
	/**
	 * pack object to buffer
	 *
	 * @param
	 *        	obj mixed<p>
	 *        Object will be convert to array inside this call.
	 *        	</p>
	 * @return bool <p> on success : true </p>
	 *         <p> on error : false </p>
	 */
	function pack($arrobj, $seqid)
	{
		$extra = '';
		$this->clear();
		$rc = $this->feed(internal\build_packet($arrobj, $seqid), $extra);
		if ( internal\E_OK != $rc)
		{
			return true;
		}
		
		return true;
	}

	/**
	 * unpack object from buffer
	 *
	 * @param
	 *        	strbuf string<p>
	 *        	</p>
	 * @return bool <p> on success : true </p>
	 *         <p> on error : false </p>
	 */
	function unpack($strbuf)
	{
		$extra = '';
		$this->clear();
		$rc = $this->feed($strbuf, $extra);
		
		if ( internal\E_OK != $rc)
		{
			return false;
		}
		
		return true;
	}

	/**
	 * feed data
	 *
	 * @param
	 *        	data [in/out] string<p>
	 *        Some received data
	 *        	</p>
	 * @param
	 *        	extra [out]<p>
	 *        After feed, may be extra, for next time feed
	 *        	</p>
	 * @return bool <p> on success : E_OK </p>
	 *         <p> on error : other </p>
	 */
	function feed(&$data, &$extra) {
		return $this->parser->feed ( $data, $extra );
	}
}