<?php

namespace Leadingfellows\DrupalComposerHelper;

use Composer\Composer;

use Composer\Plugin\PluginInterface;
use Composer\IO\IOInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\ScriptEvents;


class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer
     */
    private $composer;


	/**
     * @var Options
     */
    private $options;


	/**
     * @var IOInterface
     */
    protected $io;

    protected static $dirToRemoveGitDirectories = [
		"modules",
		"themes",
		"profiles",
		"libraries"
	];

	/**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
		$this->io = $io;
        $this->options = new Options($composer);
        if ($this->io->isVeryVerbose() || $this->io->isDebug()) {
            $this->io->write("activate composer plugin drupal-remove-git-directories");
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => [
                ['removeGitDirectories', 100],
            ],
            ScriptEvents::POST_UPDATE_CMD => [
                ['removeGitDirectories', 100],
            ],
        ];
    }

    public function isActive() {
        $activated = $this->options->get('active');
        return ($activated)? true:false;
    }

    public function processDrupalDirectory() {
        $activated = $this->options->get('directories-drupal');
        return ($activated)? true:false;
    }

    public function processVendorDirectory() {
        $activated = $this->options->get('directories-vendor');
        return ($activated)? true:false;
    }

	protected function getDrupalRoot() {
        $composer_root = getcwd();
		// with options
		$drupal_root = $composer_root . '/' . $this->options->get('web-prefix');
    	// otherwise with DrupalFinder
		// $drupalFinder = new DrupalFinder();
		// $drupalFinder->locateRoot($composer_root);
		// $drupal_root = $drupalFinder->getDrupalRoot();
		return $drupal_root;
	}

    protected function getVendorDirectory() {
        $composer_root = getcwd();
        return $composer_root . '/vendor';
    }
	
    
	/**
     *
     */
	public function removeGitDirectories() {
            if(!$this->isActive()) {
                if ($this->io->isVerbose() || $this->io->isDebug()) {
                    $this->io->write("drupal-composer-remove-git: not active");
                }
                return;
            }
            if($this->processDrupalDirectory()) {
                $drupal_root = $this->getDrupalRoot();
    			foreach (static::$dirToRemoveGitDirectories as $subdirectory_to_scan) {
                    $dirToScan = $drupal_root . DIRECTORY_SEPARATOR . $subdirectory_to_scan;
                    if(!file_exists($dirToScan) || !is_dir($dirToScan)) {
                        continue;
                    }
                    $this->removeGitDirectoriesRecursive($dirToScan);
    			}
            }
            if($this->processVendorDirectory()) {
                $vendor_dir = $this->getVendorDirectory();
                $this->removeGitDirectoriesRecursive($vendor_dir);
            }
	}

    protected static function findGitDirectories($parent_dir, &$result) {
        if(!is_dir($parent_dir)) return;
        else if ($handle = opendir($parent_dir)) 
        {
            while (false !== ($file = readdir($handle)))
            {
                if(in_array($file, array('.', '..'))) continue;
                else {
                    $path = $parent_dir . DIRECTORY_SEPARATOR . $file;
                    if(!is_dir($path))  continue;
                    if($file == ".git") $result []= $path;
                    else static::findGitDirectories($path, $result);
                }
            }
            closedir($handle);
        }
    }

    protected function removeGitDirectoriesRecursive($dirToScan) {
        if ($this->io->isVerbose() || $this->io->isDebug()) {
                $this->io->write("scan directory for '.git': ". $dirToScan);
        }
        $git_directories = array();
        static::findGitDirectories($dirToScan, $git_directories);
        foreach($git_directories as $path){
                if ($this->io->isVerbose() || $this->io->isDebug()) {
                    $this->io->write("delete directory ". $path);
                }
                static::deleteRecursive($path);
        }
    }

	/**
     * Helper method to remove directories and the files they contain.
     *
     * @param string $path
     *   The directory or file to remove. It must exist.
     *
     * @return bool
     *   TRUE on success or FALSE on failure.
     */
    protected static function deleteRecursive($path)
    {
        if (is_file($path) || is_link($path)) {
            return unlink($path);
        }
        $success = true;
        $dir = dir($path);
        while (($entry = $dir->read()) !== false) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            $entry_path = $path . DIRECTORY_SEPARATOR . $entry;
            $success = static::deleteRecursive($entry_path) && $success;
        }
        $dir->close();
        return rmdir($path) && $success;
    }
}

