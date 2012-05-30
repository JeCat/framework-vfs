<?php
namespace org\jecat\framework\fs\vfs ;

class SaeStorageFileSystem implements IPhysicalFileSystem
{
	public function __construct($sDomainName){
		$this->sDomainName = $sDomainName ;
	}
	
	public function stor() {
		if ( !isset( $this->stor ) ) $this->stor = new \SaeStorage();
		return $this->stor;
	}

	public function & stream_open( $path , $mode , $options , &$opened_path)
	{
		$arrFileInfo = array();
		$arrFileInfo['domain'] = $this->sDomainName ;
		$arrFileInfo['file'] = ltrim($path,'/\\');
		$arrFileInfo['position'] = 0;
		$arrFileInfo['mode'] = $mode;
		$arrFileInfo['options'] = $options;
		$arrFileInfo['fcontent'] = '';
		$arrFileInfo['writen'] = true;
		
		if ( in_array( $arrFileInfo['mode'], array( 'r', 'r+', 'rb' ) ) ) {
			if ( $arrFileInfo['fcontent'] = $this->stor()->read($arrFileInfo['domain'], $arrFileInfo['file']) ) {
			} else {
				trigger_error("fopen({$path}): failed to read from Storage: No such domain or file.", E_USER_WARNING);
				return false;
			}
		} elseif ( in_array( $arrFileInfo['mode'], array( 'a', 'a+', 'ab' ) ) ) {
			trigger_error("fopen({$path}): Sorry, saestor does not support appending", E_USER_WARNING);
			if ( $arrFileInfo['fcontent'] = $this->stor()->read($arrFileInfo['domain'], $arrFileInfo['file']) ) {
			} else {
				trigger_error("fopen({$path}): failed to read from Storage: No such domain or file.", E_USER_WARNING);
				return false;
			}
		} elseif ( in_array( $arrFileInfo['mode'], array( 'x', 'x+', 'xb' ) ) ) {
			if ( !$this->stor()->getAttr($arrFileInfo['domain'], $arrFileInfo['file']) ) {
				$arrFileInfo['fcontent'] = '';
			} else {
				trigger_error("fopen({$path}): failed to create at Storage: File exists.", E_USER_WARNING);
				return false;
			}
		} elseif ( in_array( $arrFileInfo['mode'], array( 'w', 'w+', 'wb' ) ) ) {
			$arrFileInfo['fcontent'] = '';
		} else {
			$arrFileInfo['fcontent'] = $this->stor()->read($arrFileInfo['domain'], $arrFileInfo['file']);
		}
		return $arrFileInfo;
	}

	public function stream_read(&$arrFileInfo,$count)
	{
		if (in_array($arrFileInfo['mode'], array('w', 'x', 'a', 'wb', 'xb', 'ab') ) ) {
			return false;
		}

		$ret = substr( $arrFileInfo['fcontent'] , $arrFileInfo['position'], $count);
		$arrFileInfo['position'] += strlen($ret);

		return $ret;
	}

	public function stream_write(&$arrFileInfo,$data)
	{
		if ( in_array( $arrFileInfo['mode'], array( 'r', 'rb' ) ) ) {
			return false;
		}

		// print_r("WRITE\tcontent:".strlen($arrFileInfo['fcontent'])."\tposition:".$arrFileInfo['position']."\tdata:".strlen($data)."\n");

		$left = substr($arrFileInfo['fcontent'], 0, $arrFileInfo['position']);
		$right = substr($arrFileInfo['fcontent'], $arrFileInfo['position'] + strlen($data));
		$arrFileInfo['fcontent'] = $left . $data . $right;

		$arrFileInfo['position'] += strlen($data);
		if ( strlen( $data ) > 0 )
			$arrFileInfo['writen'] = false;

		return strlen( $data );
		//}
		//else return false;
	}

	public function stream_close(&$arrFileInfo)
	{
		if (!$arrFileInfo['writen']) {
			$this->stor()->write( $arrFileInfo['domain'], $arrFileInfo['file'], $arrFileInfo['fcontent'] );
			$arrFileInfo['writen'] = true;
		}
	}


	public function stream_eof(&$arrFileInfo)
	{

		return $arrFileInfo['position'] >= strlen( $arrFileInfo['fcontent']  );
	}

	public function stream_tell(&$arrFileInfo)
	{
		return $arrFileInfo['position'];
	}

	public function stream_seek(&$arrFileInfo,$offset , $whence = SEEK_SET)
	{


		switch ($whence) {
			case SEEK_SET:

				if ($offset < strlen( $arrFileInfo['fcontent'] ) && $offset >= 0) {
					$arrFileInfo['position'] = $offset;
					return true;
				}
				else
					return false;

				break;

			case SEEK_CUR:

				if ($offset >= 0) {
					$arrFileInfo['position'] += $offset;
					return true;
				}
				else
					return false;

				break;

			case SEEK_END:

				if (strlen( $arrFileInfo['fcontent'] ) + $offset >= 0) {
					$arrFileInfo['position'] = strlen( $arrFileInfo['fcontent'] ) + $offset;
					return true;
				}
				else
					return false;

				break;

			default:

				return false;
		}
	}

	public function unlink($path)
	{
		return $this->stor()->delete(
			$this->sDomainName ,
			ltrim($path, '/\\')
		);
	}

	public function stream_flush(&$arrFileInfo) {
		if (!$arrFileInfo['writen']) {
			$this->stor()->write( $arrFileInfo['domain'], $arrFileInfo['file'], $arrFileInfo['fcontent'] );
			$arrFileInfo['writen'] = true;
		}

		return $arrFileInfo['writen'];
	}

	public function stream_stat() {
		echo __METHOD__ , '<br />' ;
		return array();
	}

	public function url_stat($path, $flags)
	{
		$arrFileInfo['domain'] = $this->sDomainName ;
		$arrFileInfo['file'] = ltrim($path,'/\\');
		
		// 文件
		if ( $attr = $this->stor()->getAttr( $arrFileInfo['domain'] , $arrFileInfo['file'] ) ) {
			$stat = array();
			$stat['dev'] = $stat[0] = 0x8001;
			$stat['ino'] = $stat[1] = 0;;
			$stat['mode'] = $stat[2] = 33279; //0100000 | 0777;
			$stat['nlink'] = $stat[3] = 0;
			$stat['uid'] = $stat[4] = 0;
			$stat['gid'] = $stat[5] = 0;
			$stat['rdev'] = $stat[6] = 0;
			$stat['size'] = $stat[7] = $attr['length'];
			$stat['atime'] = $stat[8] = 0;
			$stat['mtime'] = $stat[9] = $attr['datetime'];
			$stat['ctime'] = $stat[10] = $attr['datetime'];
			$stat['blksize'] = $stat[11] = 0;
			$stat['blocks'] = $stat[12] = 0;
			return $stat;
		// 目录
		} else {
			$arrFileInfo['file'] = rtrim($arrFileInfo['file'], '/\\');
			
			// 检查是否为空
			$arrFileList = $this->stor()->getList($arrFileInfo['domain'],$arrFileInfo['file'].'/*',1) ;
			if( empty($arrFileList) )
			{
				return false ;
			}
			
			else 
			{
				$stat = array();
				$stat['dev'] = $stat[0] = 0x8001;
				$stat['ino'] = $stat[1] = 0;;
				$stat['mode'] = $stat[2] = 16895; //040000 | 0777;
				$stat['nlink'] = $stat[3] = 0;
				$stat['uid'] = $stat[4] = 0;
				$stat['gid'] = $stat[5] = 0;
				$stat['rdev'] = $stat[6] = 0;
				$stat['size'] = $stat[7] = 4096;
				$stat['atime'] = $stat[8] = 0;
				$stat['mtime'] = $stat[9] = 0;
				$stat['ctime'] = $stat[10] = 0;
				$stat['blksize'] = $stat[11] = 0;
				$stat['blocks'] = $stat[12] = 0;
			}
			return $stat;
		}
	}

	public function dir_closedir() {
		echo __METHOD__ , '<br />' ;
		return false;
	}

	public function dir_opendir($path, $options) {
		echo __METHOD__ , '<br />' ;
		return true;
	}

	public function dir_readdir() {
		echo __METHOD__ , '<br />' ;
		return false;
	}

	public function dir_rewinddir() {
		echo __METHOD__ , '<br />' ;
		return false;
	}

	public function mkdir($path, $mode, $options)
	{
		$rtn = $this->stor()->write(
			$this->sDomainName , 
			$path.'/__________sae-dir-tag',
			'This is created by system!'
		);
		if( $rtn === false ){
			return false;
		}
		return true;
	}

	public function rename($path_from, $path_to) {
		echo __METHOD__ , '<br />' ;
		return false;
	}

	public function rmdir($path, $options) {
		echo __METHOD__ , '<br />' ;
		return true ;
	}

	public function stream_cast($cast_as) {
		echo __METHOD__ , '<br />' ;
		return false;
	}

	public function stream_lock(&$arrFileInfo,$operation) {
		echo __METHOD__ , '<br />' ;
		return false;
	}

	public function stream_set_option($option, $arg1, $arg2) {
		echo __METHOD__ , '<br />' ;
		return false;
	}



/********************************************************************/
	/**
	 * @return resource
	 */
	public function & openFile($sPath,$sMode,$options,&$opened_path){
		$f =  $this->stream_open($sPath,$sMode,$options,$opened_path);
		return $f;
	}
	
	/**
	 * @return void
	 */
	public function closeFile(&$arrFileInfo){
		return $this->stream_close($arrFileInfo);
	}
	
	/**
	 * @return bool
	 */
	public function endOfFile(&$arrFileInfo){
		return $this->stream_eof($arrFileInfo);
	}
	
	/**
	 * @return bool
	 */
	public function lockFile(&$arrFileInfo,$operation){
		return $this->stream_lock($arrFileInfo,$operation);
	}
	
	/**
	 * @return bool
	 */
	public function flushFile(&$arrFileInfo){
		return $this->stream_flush($arrFileInfo);
	}
	
	/**
	 * @return string
	 */
	public function readFile(&$arrFileInfo,$nLength){
		return $this->stream_read($arrFileInfo,$nLength);
	}
	
	/**
	 * @return bool
	 */
	public function seekFile(&$arrFileInfo,$offset,$whence=SEEK_SET){
		return $this->stream_seek($arrFileInfo,$offset,$where);
	}
	
	/**
	 * @return bool
	 */
	public function tellFile(&$arrFileInfo){
		return $this->stream_tell($arrFileInfo);
	}
	
	/**
	 * @return int
	 */
	public function writeFile(&$arrFileInfo,$data){
		return $this->stream_write($arrFileInfo,$data);
	}
	
	/**
	 * @return bool
	 */
	public function unlinkFile($sPath){
		return $this->unlink($sPath);
	}
	
	/**
	 * @return array
	 */
	public function stat($sPath,$flags){
		return $this->url_stat($sPath,$flags);
	}
	
	/**
	 * @return resource
	 */
	public function opendir($sPath,$options){
		return $this->dir_opendir($sPath,$options);
	}

	/**
	 * @return string
	 */
	public function readdir(&$resource){
		return $this->dir_readdir();
	}
	
	/**
	 * @return bool
	 */
	public function closedir(&$resource){
		return $this->dir_closedir();
	}
	
	/**
	 * @return bool
	 */
	public function rewinddir(&$resource){
		return $this->dir_rewinddir();
	}

	public function url($sPath){
		return $sPath;
	}
	
	private $sDomainName ;
}
