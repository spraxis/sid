<?php
namespace Sebas\Sid\Model;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\Module\FullModuleList;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;

class Sid extends Command
{
    // Just enter the following information and you're good to go
    const COMPANY = 'Company_Name'; // check folder name under app/design/[COMPANY]
    const THEME = 'Theme_Name'; // check folder name under app/design/COMPANY/[THEME]
    const STORE = 'Store_Name'; // check the 'store.name' field on the database
    // ----------------------------

    const COMMAND = 'sid';

    protected $moduleList;
    protected $fullModuleList;
    protected $directoryList;
    protected $resource;
    protected $config;
    protected $folderCache;

    public function __construct(
        ModuleList $moduleList,
        FullModuleList $fullModuleList,
        DirectoryList $directoryList,
        ResourceConnection $resource,
        Config $config
    )
    {
        $this->moduleList = $moduleList;
        $this->fullModuleList = $fullModuleList;
        $this->directoryList = $directoryList;
        $this->resource = $resource;
        $this->config = $config;

        $this->varCache = $this->directoryList->getRoot().'/var/cache/';
        $this->varPageCache = $this->directoryList->getRoot().'/var/page_cache/';
        $this->varGeneration = $this->directoryList->getRoot().'/var/generation/';
        $this->varViewPreprocessed = $this->directoryList->getRoot().'/var/view_preprocessed/';
        $this->pubStatic = $this->directoryList->getRoot().'/pub/static/';
        $this->themeStyles = $this->directoryList->getRoot().'/pub/static/frontend/'.self::COMPANY.'/THEMENAME/en_US/css/';

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName(self::COMMAND)
            ->setDefinition(
                array(
                    new InputArgument('action', InputArgument::OPTIONAL, 'The custom argument', null),
                    new InputOption('t', '', InputOption::VALUE_OPTIONAL, 'Path to the template, starting with vendor/', null),
                    new InputOption('m', '', InputOption::VALUE_OPTIONAL, 'Name of the module', null),
                    new InputOption('v', '', InputOption::VALUE_OPTIONAL, 'Desired version of the module', null)
                )
            )
            ->setDescription('Company specific command')
            ->setHelp(<<<EOF
<info>$ %command.full_name% modules:company (m:c)</info> List all the modules of your company (with its code version)
<info>$ %command.full_name% clean:all (c:a)</info> Removes all cache (everything within /pub/static and /var)
<info>$ %command.full_name% clean:styles (c:s)</info> Removes the specific cache to regenerate the CSS styles
<info>$ %command.full_name% clean:layouts (c:l)</info> Removes the specific cache to regenerate the layouts
<info>$ %command.full_name% clean:templates (c:t)</info> Removes the specific cache to regenerate the templates
<info>$ %command.full_name% override:template (o:t) --t="vendor/..."</info> Returns the path to our theme in order to override a core template
<info>$ %command.full_name% module:downgrade (m:d) --m="ModuleName" (just the name after the underscore)</info> Downgrades the version of the database module to the one on the code
<info>$ %command.full_name% hint:on (h:on)</info> Enables the Template Hints
<info>$ %command.full_name% hint:off (h:off)</info> Disables the Template Hints
EOF
        );

        parent::configure();
    }



    protected function execute(
        InputInterface $input,
        OutputInterface $output
    )
    {
        switch($input->getArgument('action')) :

            case 'modules:company' :
            case 'm:company' : case 'modules:c' :
            case 'm:c' :
                $output->writeln('<info>List enabled modules of the company and its code version:</info>');
                foreach($this->moduleList->getAll() as $m) {
                    if (strpos($m['name'], self::COMPANY) !== false) {
                        $output->writeln($m['name'].' -> '.$m['setup_version']);
                    }
                }
                $output->writeln('');

                $output->writeln('<info>List of disabled modules of the company:</info>');
                $enabledModules = $this->moduleList->getNames();
                $disabledModules = array_diff($this->fullModuleList->getNames(), $enabledModules);

                foreach($disabledModules as $dm) {
                    if (strpos($dm, self::COMPANY) !== false) {
                        $output->writeln($dm);
                    }
                }
            break;



            case 'module:downgrade' :
            case 'm:downgrade' : case 'module:d' :
            case 'm:d' :
                if(null !== $input->getOption('m')) {
                    $module = self::COMPANY.'_'.$input->getOption('m');

                    // Get current db version
                    $v = null;
                    foreach($this->moduleList->getAll() as $m) {
                        if($m['name'] == $module) {
                            $v = $m['setup_version'];
                        }
                    }

                    if(null !== $v) {
                        $connection = $this->resource->getConnection('default');

                        $result = $connection->fetchRow(
                            "SELECT schema_version FROM setup_module WHERE module = '$module'
                        ");
                        $currentVersion = $result['schema_version'];

                        if($v !== $currentVersion) {
                            $connection->query(
                                "UPDATE setup_module SET schema_version = '$v', data_version = '$v' WHERE module = '$module' LIMIT 1"
                            );
                            $output->writeln("<info>$module</info>: database version downgraded to <info>$v</info>");
                        } else {
                            $output->writeln("<info>$module</info>: the database version was the same as the code version, nothing to do here");
                        }

                    } else {
                        $output->writeln("We couldn't find any module under the name <info>$module</info>");
                    }

                } else {
                    $output->writeln('The option <info>--m="ModuleName"</info> (just the part after '.self::COMPANY.'_) is required');
                }
            break;



            case 'hints:on' :
            case 'h:on' :
                $storeId = null;

                $connection = $this->resource->getConnection('default');
                $result = $connection->fetchRow("SELECT store_id FROM store WHERE name = '".self::STORE."'");
                $storeId = $result['store_id'];

                if(null !== $storeId) {

                    $connection = $this->resource->getConnection('default');
                    $result = $connection->fetchRow("
                        SELECT config_id FROM core_config_data
                        WHERE path = 'dev/debug/template_hints_storefront'
                          AND scope_id = $storeId
                          AND value = 1
                    ");
                    if(null !== $result['config_id']) {
                        $output->writeln("Templates Hints were already <info>enabled</info> for the <info>".self::THEME."</info> theme");
                    } else {
                        // Enable the Template Hints
                        $this->config->saveConfig('dev/debug/template_hints_storefront', 1, 'stores', $storeId);

                        // Remove required cache
                        $this->deleteDirectory($this->varCache);
                        $this->deleteDirectory($this->varPageCache);

                        $output->writeln("Templates Hints are now <info>enabled</info> for the <info>".self::STORE."</info> theme");
                    }

                } else {
                    $output->writeln("We couldn't find any storeId for the <info>".self::STORE."</info> store");
                }
            break;



            case 'hints:off' :
            case 'h:off' :
                $storeId = null;

                $connection = $this->resource->getConnection('default');
                $result = $connection->fetchRow("SELECT store_id FROM store WHERE name = '".self::STORE."'");
                $storeId = $result['store_id'];

                if(null !== $storeId) {

                    $connection = $this->resource->getConnection('default');
                    $result = $connection->fetchRow("
                        SELECT config_id FROM core_config_data
                        WHERE path = 'dev/debug/template_hints_storefront'
                          AND scope_id = $storeId
                          AND value = 1
                    ");
                    if(null !== $result['config_id']) {
                        // Disable the Template Hints
                        $this->config->deleteConfig('dev/debug/template_hints_storefront', 'stores', $storeId);

                        // Remove required cache
                        $this->deleteDirectory($this->varCache);
                        $this->deleteDirectory($this->varPageCache);

                        $output->writeln("Templates Hints are now <info>disabled</info>");
                    } else {
                        $output->writeln("Templates Hints were already <info>disabled</info>");
                    }

                } else {
                    $output->writeln("We couldn't find any storeId for the <info>".self::STORE."</info> store");
                }
            break;



            case 'clean:all' :
            case 'c:all' : case 'clean:a' :
            case 'c:a' :
                $this->deleteDirectory($this->pubStatic);
                $this->deleteDirectory($this->varCache);
                $this->deleteDirectory($this->varPageCache);
                $this->deleteDirectory($this->varViewPreprocessed);

                $output->writeln('<info>Done!</info>');
                break;



            case 'clean:styles' :
            case 'c:styles' : case 'clean:s' :
            case 'c:s' :
                $themeRoot = str_replace('THEMENAME', self::THEME, $this->themeStyles);
                $this->deleteDirectory($themeRoot); // css in pub/static
                $this->deleteDirectory($this->varCache);
                $this->deleteDirectory($this->varPageCache);
                $this->deleteDirectory($this->varViewPreprocessed);

                $output->writeln('<info>Done!</info>');
            break;



            case 'clean:layouts' : case 'clean:templates' :
            case 'c:layouts' : case 'c:templates' :
            case 'clean:l' : case 'clean:t' :
            case 'c:l' : case 'c:t' :
                $this->deleteDirectory($this->varCache);
                $this->deleteDirectory($this->varPageCache);

                $output->writeln('<info>Done!</info>');
            break;



            case 'override:template' :
            case 'o:template' : case 'override:t' :
            case 'o:t' :
                if(null !== $input->getOption('t')) {
                    $vendorFile = $input->getOption('t');
                    $vFile = str_replace('code/vendor', 'vendor', $vendorFile);
                    // ie of $vFile: vendor/magento/module-checkout/view/frontend/templates/cart.phtml

                    $m = explode('/', $vFile);
                    $module = explode('module-', $m[2]);
                    $module = end($module);
                    $module = str_replace('-', ' ', $module);
                    $module = ucwords($module);
                    $module = str_replace(' ', '', $module); // ie: Checkout

                    $t = explode('view', $vFile);
                    $template = end($t);
                    $template = str_replace('frontend/', '', $template);
                    $template = str_replace('base/', '', $template); // ie: /templates/cart.phtml

                    $dest = 'app/design/frontend/'.self::COMPANY.'/'.self::THEME.'/Magento_';
                    $dest .= $module;
                    $dest .= $template;

                    $output->writeln('
Override the template by copying it in the <info>'.$dest.'</info> directory.
');
                } else {
                    $output->writeln('
The option <info>--t="TemplateFile"</info> is required.
Check all the available actions with <info>bin/magento company --help</info>
');
                }
            break;



            case 'artur' :
                $output->writeln('');
                $arturPhrases = array(
                    '"Paso como Pinocho en un detector de metales"',
                    '"Le va a entrar como sordo al bombo"',
                    '"Cuando la soga viene con mierda, agarrala con la boca"',
                    '"Lo que no se va en lagrimas se va en suspiros"',
                    '"Asi es la vida, unos cogen otros miran"',
                    '"La religion me dice que hoy tengo q usar el disco"',
                    '"Mas lindo que pisar mierda en pata"',
                    '"Peor es casarse con la suegra"',
                    '"No me voy a cagar antes de tomar la purga"',
                    '"Es tan pelotudo que necesita el culo de un vaso para hacer la O"',
                    '"Ya te voy a agarrar cagando sin papel"'
                );
                $output->writeln('<info>'.$arturPhrases[array_rand($arturPhrases)].'</info>');
                $output->writeln('');
            break;



            default :
                $output->writeln('');
                if(null !== $input->getArgument('action')) {
                    $output->writeln('<info>'.$input->getArgument('action').'</info> is not defined <info>¯\_(ツ)_/¯</info>');
                } else {
                    $output->writeln('<info>¯\_(ツ)_/¯</info>');
                }
                $output->writeln('Check all the available actions with <info>bin/magento '.self::COMMAND.' --help</info>');
                $output->writeln('');
        endswitch;
    }



    function deleteDirectory($dir)
    {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir) || is_link($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..' || $item == '.htaccess') { continue; }
            if (!$this->deleteDirectory($dir . "/" . $item, false)) {
                chmod($dir . "/" . $item, 0777);
                if (!$this->deleteDirectory($dir . "/" . $item, false)) return false;
            };
        }
        return true;
    }
}
