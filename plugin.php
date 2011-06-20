<?php 
/**
 * @copyright Center for History and New Media, 2008-2010
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Dropbox
 */

define('DROPBOX_DIR', dirname(__FILE__));

// Define hooks
add_plugin_hook('after_save_form_item', 'dropbox_save_files');
add_plugin_hook('admin_append_to_items_form_files', 'dropbox_list');
add_plugin_hook('define_acl', 'dropbox_define_acl');

// Define filters
add_filter('admin_navigation_main', 'dropbox_admin_nav');

function dropbox_admin_nav($navArray)
{
    if (has_permission('Dropbox_Index', 'index')) {
        $navArray['Dropbox'] = uri(array('module'=>'dropbox', 'controller'=>'index', 'action'=>'index'), 'default');
    }
    return $navArray;
}

function dropbox_define_acl($acl)
{
    $acl->loadResourceList(array('Dropbox_Index' => array('index','add')));
}

function dropbox_list()
{
	common('dropboxlist', array(), 'index');
}  

function dropbox_save_files($item, $post) 
{
    if (!dropbox_can_access_files_dir()) {
        throw new Dropbox_Exception('Please make the following dropbox directory writable: ' . $filesDir);
    }
    
    $fileNames = $_POST['dropbox-files'];
	if ($fileNames) {
	    $filePaths = array();
		foreach($fileNames as $fileName) { 
			$filePath = PLUGIN_DIR.DIRECTORY_SEPARATOR.'Dropbox'.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.$fileName; 			
			if (!dropbox_can_access_file($filePath)) {
		        throw new Dropbox_Exception('Please make the following dropbox file readable and writable: ' . $filePath);
		    }
			$filePaths[] = $filePath;                  
		}
		
		$files = array();
		try {
			$files = insert_files_for_item($item, 'Filesystem', $filePaths);
		} catch (Exception $e) {
		    release_object($files);
		    throw $e;
		}
        release_object($files);
        
        // delete the files
        foreach($filePaths as $filePath) {
            try {
                unlink($filePath);
            } catch (Exception $e) {
                throw $e;
            }
        }	
	}
}

function dropbox_get_files_dir_path()
{
    return DROPBOX_DIR . '/files';
}

function dropbox_can_access_files_dir()
{
    $filesDir = dropbox_get_files_dir_path();
    return dropbox_can_access_file($filesDir);
}

function dropbox_can_access_file($filePath)
{
	return (is_readable($filePath) && is_writable($filePath));
}

function dropbox_dir_list($directory) 
{
    // create an array to hold directory list
    $filenames = array();

    $iter = new DirectoryIterator($directory);

    foreach ($iter as $fileEntry) {
        if ($fileEntry->isFile()) {
            $filename = $fileEntry->getFilename();
            if ($filename != '.svn') {
                $filenames[] = $filename;
            }
        }
    }

    natcasesort($filenames);

    return $filenames;
}
