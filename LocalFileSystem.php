<?php
namespace org\jecat\framework\fs\vfs ;

class LocalFileSystem implements IPhysicalFileSystem
{
	public function __construct($sRootPath)
	{
		$this->sRootPath = $sRootPath ;
	}


	/**
	 * @return resource
	 */
	public function & openFile($sPath,$sMode,$options,&$opened_path)
	{
		$opened_path = $this->sRootPath.'/'.$sPath ;
		
		$fo = fopen($opened_path,$sMode) ;
		return $fo;
	}
	/**
	 * @return void
	 */
	public function closeFile(&$resource)
	{
		return fclose($resource) ;
	}
	/**
	 * @return bool
	 */
	public function endOfFile(&$resource)
	{
		return feof($resource) ;
	}
	/**
	 * @return bool
	 */
	public function lockFile(&$resource,$operation)
	{
		return flock($resource,$operation) ;
	}
	/**
	 * @return bool
	 */
	public function flushFile(&$resource)
	{
		return flush($resource) ;
	}
	/**
	 * @return string
	 */
	public function readFile(&$resource,$nLength)
	{
		return fread($resource,$nLength) ;
	}
	/**
	 * @return bool
	 */
	public function seekFile(&$resource,$offset,$whence=SEEK_SET)
	{
		return fread($hResource,$offset,$whence) ;
	}
	/**
	 * @return bool
	 */
	public function tellFile(&$resource)
	{
		return ftell($hResource) ;
	}
	/**
	 * @return int
	 */
	public function writeFile(&$resource,$data)
	{
		return fwrite($resource,$data) ;
	}

	/**
	 * @return bool
	 */
	public function unlinkFile($sPath)
	{
		return unlink($this->sRootPath.'/'.$sPath) ;
	}
	/**
	 * @return array
	 */
	public function stat($sPath,$flags)
	{
		$sFilePath = $this->sRootPath.'/'.$sPath ;
		if( !file_exists( $sFilePath ) ){
			return false;
		}
		return stat( $sFilePath ) ;
	}
	
	/**
	 * @return resource
	 */
	public function opendir($sPath,$options)
	{
		return opendir($this->sRootPath.'/'.$sPath) ;
	}
	/**
	 * @return string
	 */
	public function readdir(&$hResource)
	{
		return readdir($hResource) ;
	}
	/**
	 * @return bool
	 */
	public function closedir(&$hResource)
	{
		return closedir($hResource) ;
	}
	/**
	 * @return bool
	 */
	public function rewinddir(&$hResource)
	{
		return rewinddir($hResource) ;
	}
	/**
	 * @return bool
	 */
	public function mkdir($sPath,$nMode,$options)
	{
		return mkdir( $this->sRootPath.'/'.$sPath, $nMode, $options ) ;
	}
	/**
	 * @return bool
	 */
	public function rename($sFrom,$sTo)
	{
		return rename( $this->sRootPath.'/'.$sFrom, $this->sRootPath.'/'.$sTo ) ;
	}
	/**
	 * @return bool
	 */
	public function rmdir($sPath,$options)
	{
		return rmdir( $this->sRootPath.'/'.$sPath ) ;
	}
	
	
	public function url($sPath)
	{
		return $this->sRootPath.'/'.$sPath ;
	}
	
	
	private $sRootPath ;
}


