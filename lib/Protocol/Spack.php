<?php
include_once 'spack_buf.php';
use talus\SpackBuf;
class Protocol_Spack
{
	
	public function version()
	{
		return SpackBuf::version();
	}

	public function pack($obj)
	{
		return SpackBuf::pack($obj);
	}


	public function unpack($buf)
	{
		return SpackBuf::unpack($buf);
	}
}