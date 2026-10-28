<?php
/**
 * CartToBudget - AJAX actions of the customer area (save / restore / delete)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class CarttobudgetAjaxModuleFrontController extends ModuleFrontController
{
    /** @var CartToBudget */
    public $module;

    public function init()
    {
        parent::init();

        if (!$this->context->customer->isLogged()) {
            $this->respond(['error' => true, 'message' => $this->module->l('You must be logged in.', 'ajax')], 401);
        }
    }

    public function postProcess()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond(['error' => true, 'message' => 'Only POST requests allowed'], 405);
        }

        if (Tools::getValue('token') !== Tools::getToken(false)) {
            $this->respond(['error' => true, 'message' => $this->module->l('Invalid token, please reload the page.', 'ajax')], 403);
        }

        $id_customer = (int) $this->context->customer->id;
        $action = Tools::getValue('action');

        try {
            switch ($action) {
                case 'save_cart':
                    $this->saveCart($id_customer);
                    break;
                case 'restore_cart':
                    $this->restoreCart($id_customer);
                    break;
                case 'delete_cart':
                    $this->deleteCart($id_customer);
                    break;
                default:
                    $this->respond(['error' => true, 'message' => 'Invalid action'], 400);
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('carttobudget: ' . $e->getMessage(), 3);
            $this->respond(['error' => true, 'message' => $this->module->l('Unexpected error, please try again.', 'ajax')], 500);
        }
    }

    protected function saveCart($id_customer)
    {
        if (!CartToBudget::isCustomerEnabled($id_customer)) {
            $this->respond(['error' => true, 'message' => $this->module->l('Saving carts is not available for your account.', 'ajax')], 403);
        }

        $name = trim((string) Tools::getValue('cart_name'));
        if ($name !== '' && !Validate::isGenericName($name)) {
            $this->respond(['error' => true, 'message' => $this->module->l('Invalid cart name.', 'ajax')], 400);
        }

        $result = $this->module->saveCurrentCart($id_customer, Tools::substr($name, 0, 255));
        if (!empty($result['success'])) {
            $this->respond([
                'success' => true,
                'message' => $this->module->l('Cart saved successfully', 'ajax'),
                'id' => $result['id'],
            ]);
        }

        $this->respond(array_merge(['error' => true], $result), 400);
    }

    protected function restoreCart($id_customer)
    {
        $budget = $this->getOwnedCart($id_customer);
        $result = $this->module->addToShoppingCart($budget, true);

        $this->respond([
            'success' => true,
            'message' => $this->module->l('Cart restored successfully', 'ajax'),
            'skipped' => $result['skipped'],
            'redirect_url' => $this->context->link->getPageLink('cart', null, null, ['action' => 'show']),
        ]);
    }

    protected function deleteCart($id_customer)
    {
        if (CartToBudget::deleteCustomerCart((int) Tools::getValue('id'), $id_customer)) {
            $this->respond(['success' => true, 'message' => $this->module->l('Cart deleted successfully', 'ajax')]);
        }

        $this->respond(['error' => true, 'message' => $this->module->l('Error deleting cart', 'ajax')], 400);
    }

    /**
     * @return CartToBudgetCart
     */
    protected function getOwnedCart($id_customer)
    {
        $budget = new CartToBudgetCart((int) Tools::getValue('id'));
        if (!Validate::isLoadedObject($budget) || (int) $budget->id_customer !== (int) $id_customer) {
            $this->respond(['error' => true, 'message' => $this->module->l('Cart not found', 'ajax')], 404);
        }
        if ($budget->source === CartToBudgetCart::SOURCE_CUSTOMER && !CartToBudget::isCustomerEnabled($id_customer)) {
            $this->respond(['error' => true, 'message' => $this->module->l('Saving carts is not available for your account.', 'ajax')], 403);
        }

        return $budget;
    }

    protected function respond(array $data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        die(json_encode($data));
    }

    public function displayAjax()
    {
        $this->postProcess();
    }
}
