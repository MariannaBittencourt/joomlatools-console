<?php
/**
 * @copyright	Copyright (C) 2007 - 2015 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		Mozilla Public License, version 2.0
 * @link		http://github.com/joomlatools/joomlatools-console for the canonical source repository
 */

namespace Joomlatools\Console\Command\Extension;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Joomlatools\Console\Command\Site\AbstractSite;

use Joomlatools\Console\Joomla\Bootstrapper;
use Joomlatools\Console\Joomla\Util;

class InstallFile extends AbstractSite
{
    protected $extension = array();

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('extension:installfile')
            ->setDescription('Install packaged extensions for file or directory into a site')
            ->setHelp(<<<EOL
You can install extensions using their installation packages using this command:

    <info>joomla extension:installfile testsite /home/vagrant/com_component.v1.x.zip /home/vagrant/plg_plugin.v2.x.tar.gz</info>

This will install both the <comment>com_component.v1.x.zip</comment> and <comment>plg_plugin.v2.x.tar.gz</comment> packages.
You can also point to the directory where the extracted package can be found.
EOL
            )
            ->addArgument(
                'extension',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'A list of full paths to extension packages (file or directory) to install'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if (Util::isJoomla4($this->target_dir)) {
            $output->write("<error>This command is not implemented for Joomla 4</error>\n");

            return;
        }

        $this->extension = $input->getArgument('extension');

        $this->check($input, $output);
        $this->install($input, $output);

        return 0;
    }

    public function check(InputInterface $input, OutputInterface $output)
    {
        if (!file_exists($this->target_dir)) {
            throw new \RuntimeException(sprintf('Site not found: %s', $this->site));
        }
    }

    public function install(InputInterface $input, OutputInterface $output)
    {
        $app = Bootstrapper::getApplication($this->target_dir);

        // Output buffer is used as a guard against Joomla including ._ files when searching for adapters
        // See: http://kadin.sdf-us.org/weblog/technology/software/deleting-dot-underscore-files.html
        ob_start();
        
        $installer = $app->getInstaller();
        
        foreach ($this->extension as $package)
        {
            $remove = false;

            if (is_file($package))
            {
                $result    = \JInstallerHelper::unpack($package);
                $directory = isset($result['dir']) ? $result['dir'] : false;

                $remove = true;
            }
            else $directory = $package;

            if ($directory !== false)
            {
                $path = realpath($directory);

                if (!file_exists($path)) {
                	$output->writeln("<info>File not found: " . $directory . "</info>\n");
                    continue;
                }

                try {
                	$installer->install($path);
                }
                catch (\Exception $e) {
                	$output->writeln("<info>Caught exception during install: " . $e->getMessage() . "</info>\n");
                }

                if ($remove) {
                    \JFolder::delete($path);
                }
            }
        }

        ob_end_clean();
    }
}
