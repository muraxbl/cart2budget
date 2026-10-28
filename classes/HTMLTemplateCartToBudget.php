<?php
/**
 * CartToBudget - PDF template, rendered by PrestaShop's PDF / TCPDF stack
 *
 * Usage: new PDF($cartToBudgetCart, 'CartToBudget', $smarty)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class HTMLTemplateCartToBudget extends HTMLTemplate
{
    /** @var CartToBudgetCart */
    public $budget;

    /** @var array */
    protected $data;

    /** @var string */
    protected $template_name;

    public function __construct($budget, $smarty, $bulk_mode = false)
    {
        $this->budget = $budget;
        $this->smarty = $smarty;
        $this->shop = new Shop((int) $budget->id_shop ?: (int) Context::getContext()->shop->id);

        $module = Module::getInstanceByName('carttobudget');
        $this->template_name = CartToBudget::resolveTemplateName($budget->template);

        $presenter = new CartToBudgetPresenter();
        $this->data = $presenter->present($budget, true);
        $this->data['template'] = $this->template_name;

        $this->date = $this->data['date'];
        $this->title = $module->l('Budget', 'htmltemplatecarttobudget') . ' ' . $this->data['reference'];

        $this->smarty->assign([
            'budget' => $this->data,
            'ctb_title' => $this->title,
            'ctb_template' => $this->template_name,
            'ctb_pdf_common' => _PS_MODULE_DIR_ . 'carttobudget/views/templates/pdf/_common/',
        ]);
    }

    public function getHeader()
    {
        return $this->smarty->fetch(CartToBudget::getPdfTemplatePath($this->template_name, 'header'));
    }

    public function getContent()
    {
        return $this->smarty->fetch(CartToBudget::getPdfTemplatePath($this->template_name, 'content'));
    }

    public function getFooter()
    {
        return $this->smarty->fetch(CartToBudget::getPdfTemplatePath($this->template_name, 'footer'));
    }

    public function getPagination()
    {
        return $this->smarty->fetch(CartToBudget::getPdfTemplatePath($this->template_name, 'pagination'));
    }

    public function getFilename()
    {
        return self::sanitizeFilename($this->data['reference'] . '-' . $this->budget->name) . '.pdf';
    }

    public function getBulkFilename()
    {
        return 'budgets.pdf';
    }

    public static function sanitizeFilename($name)
    {
        $name = Tools::str2url($name);

        return $name ?: 'budget';
    }
}
