<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

use Thirtybees\Core\InitializationCallback;
use Thirtybees\Core\Notification\FetchNotificationsTask;
use Thirtybees\Core\Notification\SystemNotification;
use Thirtybees\Core\WorkQueue\WorkQueueClient;
use Thirtybees\Core\WorkQueue\WorkQueueTask;

/**
 * Class AdminSystemNotificationControllerCore
 */
class AdminSystemNotificationControllerCore extends AdminController implements InitializationCallback
{

    /**
     * @var WorkQueueClient
     */
    protected $workQueueClient;

    /**
     * @var string
     */
    protected $_defaultOrderBy = 'importance';

    /**
     * @var string
     */
    protected $_defaultOrderWay = 'asc';

    /**
     * AdminSystemNotificationControllerCore constructor.
     *
     * @param WorkQueueClient $workQueueClient
     * @throws PrestaShopException
     */
    public function __construct(WorkQueueClient $workQueueClient)
    {
        $this->workQueueClient = $workQueueClient;
        $this->bootstrap = true;
        $this->table = 'system_notification';
        $this->className = SystemNotification::class;
        $this->lang = false;

        $this->addRowAction('view');


        $this->context = Context::getContext();

        $this->fields_list = [
            'id_system_notification' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'title' => [
                'title' => $this->l('Title'),
            ],
            'message' => [
                'title' => $this->l('Message'),
                'maxlength' => 300,
                'callback' => 'formatMessagePreview',
            ],
            'importance' => [
                'title' => $this->l('Importance'),
                'type' => 'select',
                'list' => [
                    SystemNotification::IMPORTANCE_LOW => $this->l('Low'),
                    SystemNotification::IMPORTANCE_MEDIUM => $this->l('Medium'),
                    SystemNotification::IMPORTANCE_HIGH => $this->l('High'),
                    SystemNotification::IMPORTANCE_URGENT => $this->l('Urgent'),
                ],
                'filter_key' => 'a!importance',
                'callback' => 'formatImportance',
            ],
            'date_created' => [
                'title' => $this->l('Date'),
                'type' => 'date',
            ]
        ];

        parent::__construct();
    }

    /**
     * Toolbar title
     */
    public function initToolbarTitle()
    {
        parent::initToolbarTitle();

        switch ($this->display) {
            case '':
            case 'list':
                array_pop($this->toolbar_title);
                $this->toolbar_title[] = $this->l('System Notifications');
                break;
            case 'view':
                /** @var SystemNotification $notification */
                $notification = $this->loadObject();
                if ($notification) {
                    array_pop($this->toolbar_title);
                    $this->toolbar_title[] = sprintf($this->l('System notification: %s'), $notification->title);
                }
                break;

        }
    }

    /**
     * Initialize list toolbar
     *
     * @throws PrestaShopException
     */
    public function initToolbar()
    {
        parent::initToolbar();

        // do not show 'Add new' button
        if (isset($this->toolbar_btn['new'])) {
            unset($this->toolbar_btn['new']);
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['fetch'] = [
                'href' => $this->context->link->getAdminLink('AdminSystemNotification', true, [
                    'action' => 'fetchNotifications'
                ]),
                'desc' => $this->l('Refresh'),
                'icon' => 'process-icon-refresh',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Fetch notifications from thirty bees api server
     *
     * @throws PrestaShopException
     */
    public function processFetchNotifications()
    {
        $this->setRedirectAfter($this->context->link->getAdminLink('AdminSystemNotification'));
        $task = FetchNotificationsTask::createTask();
        $result = $this->workQueueClient->runImmediately($task);
        if ($result->getStatus() === WorkQueueTask::STATUS_SUCCESS) {
            $this->confirmations[] = $this->l('Notifications successfully synchronized');
        } else {
            $this->errors[] = sprintf($this->l('Failed to synchronize notifications: %s'), $task->error);
        }
        $this->redirect();
    }

    /**
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderView()
    {
        /** @var SystemNotification $notification */
        $notification = $this->loadObject();
        if ($notification) {

            $this->tpl_view_vars = [
                'notification' => $notification,
                'show_toolbar' => true,
            ];

            return parent::renderView();
        }
        return '';
    }

    /**
     * Format message for list view
     *
     * @param string $message
     * @return string
     */
    public function formatMessagePreview($message)
    {
        return Tools::truncate(strip_tags($message), 300);
    }

    /**
     * @param string $importance
     * @return string
     */
    public function formatImportance($importance)
    {
        $badgeClass = SystemNotification::getBadgeClass($importance);
        $label = $this->translateImporance($importance);
        return '<span class="badge '.$badgeClass.'">' . $label . '</span>';
    }

    /**
     * @param string $importance
     *
     * @return string
     */
    protected function translateImporance($importance)
    {
        switch ($importance) {
            case SystemNotification::IMPORTANCE_LOW:
                return $this->l('Low');
            case SystemNotification::IMPORTANCE_MEDIUM:
                return $this->l('Medium');
            case SystemNotification::IMPORTANCE_HIGH:
                return $this->l('High');
            case SystemNotification::IMPORTANCE_URGENT:
                return $this->l('Urgent');
            default:
                return $this->l('-');
        }
    }

    /**
     * @param Db $conn
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function initializationCallback(Db $conn)
    {
        $id = (int)Tab::getIdFromClassName('AdminSystemNotification');
        if (! $id) {
            // Create tab if not exists
            $tab = new Tab();
            $tab->class_name = 'AdminSystemNotification';
            $tab->id_parent = (int)Tab::getIdFromClassName('AdminAdmin');
            $tab->name = [];
            foreach (Language::getIDs() as $langId) {
                $tab->name[$langId] = 'System notifications';
            }
            $tab->add();
            Configuration::updateGlobalValue(Configuration::SHOW_NEW_SYSTEM_NOTIFICATIONS, 1);
        }
    }
}
