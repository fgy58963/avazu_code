<?php

namespace talus;

include_once __DIR__ . '/impl/spack_inner.php';

/*
 * spack body only
 */
class SpackBuf {
	/**
	 * the version of spack protocol, which can be used in HTTP header to 
	 * check differenct version of spark
	 * @return <p> spack version
	 * </p>
	 */
	static function version()
	{
		return internal\VERSION_MAJOR . "." . internal\VERSION_MINOR;
	}
	
	/**
	 * pack object to buffer
	 *
	 * @param
	 *        	obj mixed<p>
	 *        	</p>
	 * @return <p> packed string
	 * </p>
	 */
	static function pack($obj)
	{
		return internal\build($obj);
	}

	/**
	 * unpack object from buffer
	 *
	 * @param
	 *        	buffer string<p>
	 *        	</p>
	 * @return <p> mixed
	 * </p>
	 */
	static function unpack($buffer)
	{
		return internal\parse($buffer);
	}
}