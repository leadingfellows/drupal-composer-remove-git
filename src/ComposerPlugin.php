<?php

namespace Leadingfellows\DrupalComposerHelper;

use Composer\Composer;

use Composer\Plugin\PluginInterface;
use Composer\IO\IOInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\ScriptEvents;

// use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;



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

	public function getDrupalRoot() {
		$fs = new Filesystem();
        $composer_root = getcwd();
		// with options
		$drupal_root = $composer_root . '/' . $this->options->get('web-prefix');
    	// with DrupalFinder
		// $drupalFinder = new DrupalFinder();
		// $drupalFinder->locateRoot($composer_root);
		// $drupal_root = $drupalFinder->getDrupalRoot();
		return $drupal_root;
	}
	
	/**
     *
     */
	public function removeGitDirectories() {
            if(!$this->isActive()) {
                if ($this->io->isVerbose() || $this->io->isDebug()) {
                    $this->io->write("removeGitDirectories: not active");
                }
                return;
            }
            $drupal_root = $this->getDrupalRoot();
            if ($this->io->isVerbose() || $this->io->isDebug()) {
                $this->io->write("removeGitDirectories processing Drupal root: ".$drupal_root);
            }
			foreach (static::$dirToRemoveGitDirectories as $subdirectory_to_scan) {
                $dirToScan = $drupal_root."/".$subdirectory_to_scan;
                if(!file_exists($dirToScan) || !is_dir($dirToScan)) {
                    if ($this->io->isVerbose() || $this->io->isDebug()) {
                        $this->io->write("removeGitDirectories skip directory: ".$dirToScan);
                    }
                    continue;
                }
                $Directory = new \RecursiveDirectoryIterator($dirToScan);
                $Iterator = new \RecursiveIteratorIterator($Directory);
                $Regex = new \RegexIterator($Iterator, '/^\.git$/i', \RecursiveRegexIterator::GET_MATCH);	
                foreach($objects as $name => $object){
                    $this->io->write("found: ".$name." ".print_r($object,TRUE));
                    //static::deleteRecursive(
                }
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
            $entry_path = $path . '/' . $entry;
            $success = static::deleteRecursive($entry_path) && $success;
        }
        $dir->close();
        return rmdir($path) && $success;
    }
}

