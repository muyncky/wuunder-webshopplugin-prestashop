<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class WuunderConnector extends Module
{
    public function __construct()
    {
        $this->name = 'wuunderconnector';
        $this->tab = 'wuunder';
        $this->version = '1.0.0';
        $this->author = 'Wuunder';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('WuunderConnector');
        $this->description = $this->l('Wuunder connector');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get($this->name))
            $this->warning = $this->l('No name provided');
    }

    private function installDB()
    {
        Db::getInstance()->execute('
                       CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wuunder_shipments` (
                            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                            `order_id` INT(11) UNSIGNED NOT NULL,
                            `booking_url` TEXT NULL,
                            `booking_token` varchar(255) NULL,
                            `label_id` varchar(255) NULL,
                            `label_url` TEXT NULL,
                            `label_tt_url` TEXT NULL,
                            PRIMARY KEY(`id`)
                        )
                        ENGINE = ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET = utf8;
            ');
    }

    private function uninstallDB()
    {
        Db::getInstance()->execute('
               DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'wuunder_shipments`
            ');
    }

    private function installModuleTab($tab_class, $tab_name, $id_tab_parent)
    {
        if (!copy(_PS_MODULE_DIR_ . $this->name . '/logo.png', _PS_IMG_DIR_ . 't/' . $tab_class . '.png')) {
            return false;
        }
        $tab = new Tab();

        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            $tab->name[$language['id_lang']] = $tab_name;
        }
        $tab->class_name = $tab_class;
        $tab->module = $this->name;
        $tab->id_parent = $id_tab_parent;

        if (!$tab->save()) {
            return false;
        }
        return true;
    }

    private function uninstallModuleTab($tab_class)
    {
        $id_tab = Tab::getIdFromClassName($tab_class);
        if ($id_tab != 0) {
            $tab = new Tab($id_tab);
            $tab->delete();
            return true;
        }
        return false;
    }

    public function install()
    {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);

        $this->installDB();

        if (!parent::install() ||
            !$this->installModuleTab('AdminWuunderConnector', 'Wuunder', Tab::getIdFromClassName('AdminShipping'))
        )
            return false;

        Configuration::updateValue('testmode', '1');

        return true;
    }

    public function uninstall()
    {
        $this->uninstallDB();

        if (!parent::uninstall() ||
            !Configuration::deleteByName($this->name) ||
            !$this->uninstallModuleTab('AdminWuunderConnector')
        )
            return false;

        return true;
    }

    public function getContent()
    {
        $output = null;
        $fields = array(
          "testmode",
            "live_api_key",
            "test_api_key",
            "company_name",
            "firstname",
            "lastname",
            "email",
            "phonenumber",
            "streetname",
            "housenumber",
            "zipcode",
            "city",
            "country"
        );

        if (Tools::isSubmit('submit' . $this->name)) {
            foreach ($fields as $field) {
                $field_name = strval(Tools::getValue($field));
                if ((!$field_name
                    || empty($field_name)
                    || !Validate::isGenericName($field_name))
                    && ($field !== "live_api_key"
                    && $field !== "test_api_key")
                )
                    $output .= $this->displayError($this->l('Invalid Configuration value: '.$field));
                else {
                    Configuration::updateValue($field, $field_name);
//                    $output .= $this->displayConfirmation($this->l('Settings updated'));
                }
            }
        }
        return $output.$this->displayForm();
    }

public
function displayForm()
{
    // Get default language
    $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

    // Init Fields form array
    $fields_form[0]['form'] = array(
        'legend' => array(
            'title' => $this->l('Settings'),
        ),
        'input' => array(
            array(
                'type' => 'select',
                'label' => $this->l('Testmode'),
                'name' => "testmode",
                'options' => array(
                    'query' => array(array("id" => 1, "name" => "Aan"), array("id" => 0, "name" => "Uit")),
                    'id' => 'id',
                    'name' => 'name'
                ),
                'required' => true
            ),

            array(
                'type' => 'text',
                'label' => $this->l('Live API key'),
                'name' => "live_api_key",
                'required' => false
            ),

            array(
                'type' => 'text',
                'label' => $this->l('Test API key'),
                'name' => "test_api_key",
                'required' => false
            ),

            array(
                'type' => 'text',
                'label' => $this->l('Bedrijfsnaam'),
                'name' => "company_name",
                'required' => true
            ),

            array(
                'type' => 'text',
                'label' => $this->l('Firstname'),
                'name' => "firstname",
                'required' => true
            ),

            array(
                'type' => 'text',
                'label' => $this->l('Lastname'),
                'name' => "lastname",
                'required' => true
            ),

            array(
                'type' => 'text',
                'label' => $this->l('Email'),
                'name' => "email",
                'required' => true
            ),

            array(
                'type' => 'text',
                'label' => $this->l('Phonenumber'),
                'name' => "phonenumber",
                'required' => true
            ),

            array(
                'type' => 'text',
                'label' => $this->l('Streetname'),
                'name' => "streetname",
                'required' => true
            ),

            array(
                'type' => 'text',
                'label' => $this->l('Housenumber'),
                'name' => "housenumber",
                'required' => true
            ),

            array(
                'type' => 'text',
                'label' => $this->l('Zipcode'),
                'name' => "zipcode",
                'required' => true
            ),

            array(
                'type' => 'text',
                'label' => $this->l('City'),
                'name' => "city",
                'required' => true
            ),

            array(
                'type' => 'text',
                'label' => $this->l('Country code'),
                'name' => "country",
                'required' => true
            )
        ),
        'submit' => array(
            'title' => $this->l('Save'),
            'class' => 'btn btn-default pull-right'
        )
    );

    $helper = new HelperForm();

    // Module, token and currentIndex
    $helper->module = $this;
    $helper->name_controller = $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
    $helper->identifier = $this->name;

    // Language
    $helper->default_form_language = $default_lang;
    $helper->allow_employee_form_lang = $default_lang;

    // Title and toolbar
    $helper->title = $this->displayName;
    $helper->show_toolbar = true;        // false -> remove toolbar
    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
    $helper->submit_action = 'submit' . $this->name;
    $helper->toolbar_btn = array(
        'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
        'back' => array(
            'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Back to list')
        )
    );

    // Load current value

    $fields = array(
        "testmode",
        "live_api_key",
        "test_api_key",
        "company_name",
        "firstname",
        "lastname",
        "email",
        "phonenumber",
        "streetname",
        "housenumber",
        "zipcode",
        "city",
        "country"
    );

    foreach ($fields as $field) {
        $helper->fields_value[$field] = Configuration::get($field);
    }

    return $helper->generateForm($fields_form);
}

public
function display($file, $template, $cache_id = NULL, $compile_id = NULL)
{
    echo "hi";
}
}