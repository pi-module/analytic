<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt New BSD License
 */

/**
 * @author Hossein Azizabadi <azizabadi@faragostaresh.com>
 */

namespace Module\Analytic\Controller\Admin;

use Pi;
use Pi\Mvc\Controller\ActionController;
use Zend\Db\Sql\Predicate\Expression;

class IndexController extends ActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute('', [
            'controller' => 'index',
            'action'     => 'summary',
        ]);
    }

    public function summaryAction()
    {
        // Set list
        $list = [];

        // Select total order
        $columns = [
            'sum'   => new Expression('sum(total_price)'),
            'count' => new Expression('count(*)'),
        ];
        $where   = ['status_order' => [1, 2, 3, 7]];
        $select  = Pi::model('order', 'order')->select()->columns($columns)->where($where);
        $rowset  = Pi::model('order', 'order')->selectWith($select);
        foreach ($rowset as $row) {
            $list['order']['sum']   = $row->sum;
            $list['order']['count'] = $row->count;
        }

        // Select total invoice
        $columns = [
            'sum'   => new Expression('sum(total_price)'),
            'count' => new Expression('count(*)'),
        ];
        $where   = ['status' => [1, 2]];
        $select  = Pi::model('invoice', 'order')->select()->columns($columns)->where($where);
        $rowset  = Pi::model('invoice', 'order')->selectWith($select);
        foreach ($rowset as $row) {
            $list['total']['sum']   = $row->sum;
            $list['total']['count'] = $row->count;
        }

        // Select paid invoice
        $columns = [
            'sum'   => new Expression('sum(total_price)'),
            'count' => new Expression('count(*)'),
        ];
        $where   = ['status' => [1, 2], 'time_payment > ?' => 0];
        $select  = Pi::model('invoice', 'order')->select()->columns($columns)->where($where);
        $rowset  = Pi::model('invoice', 'order')->selectWith($select);
        foreach ($rowset as $row) {
            $list['paid']['sum']   = $row->sum;
            $list['paid']['count'] = $row->count;
        }

        // Select unpaid invoice
        $columns = [
            'sum'   => new Expression('sum(total_price)'),
            'count' => new Expression('count(*)'),
        ];
        $where   = ['status' => [1, 2], 'time_payment' => 0];
        $select  = Pi::model('invoice', 'order')->select()->columns($columns)->where($where);
        $rowset  = Pi::model('invoice', 'order')->selectWith($select);
        foreach ($rowset as $row) {
            $list['unPaid']['sum']   = $row->sum;
            $list['unPaid']['count'] = $row->count;
        }

        // Select unpaid invoice
        $columns = [
            'sum'   => new Expression('sum(total_price)'),
            'count' => new Expression('count(*)'),
        ];
        $where   = ['status' => [1, 2], 'time_payment' => 0, 'time_duedate < ?' => time()];
        $select  = Pi::model('invoice', 'order')->select()->columns($columns)->where($where);
        $rowset  = Pi::model('invoice', 'order')->selectWith($select);
        foreach ($rowset as $row) {
            $list['delayed']['sum']   = $row->sum;
            $list['delayed']['count'] = $row->count;
        }

        // Set view
        $this->view()->setTemplate('analytic-summary');
        $this->view()->assign('list', $list);
    }

    public function companyAction()
    {
        // Get read
        $role = $this->params('role');

        // Get config
        $config = Pi::service('registry')->config->read($this->getModule());

        // Get system roles
        $systemRoles = Pi::registry('role')->read(['section' => 'front']);
        unset($systemRoles['member']);
        unset($systemRoles['webmaster']);
        unset($systemRoles['panel']);
        unset($systemRoles['guest']);

        if ($role && isset($systemRoles[$role])) {
            // Set company
            $company = $systemRoles[$role];

            // Get uid list
            $model   = Pi::model('user_role');
            $where   = ['role' => $role];
            $order   = ['uid ASC'];
            $select  = $model->select()->where($where)->order($order);
            $rowset  = $model->selectWith($select);
            $uidList = [];
            foreach ($rowset as $row) {
                $uidList[(int)$row['uid']] = (int)$row['uid'];
            }

            // Get user list
            $users           = [];
            $userInformation = [];
            $userList        = Pi::service('user')->mget($uidList,
                ['uid', 'name', 'active', 'first_name', 'last_name', 'email']
            );
            foreach ($userList as $userSingle) {
                $userSingle['id']          = $userSingle['uid'];
                $users[$userSingle['uid']] = $userSingle;
            }

            // Get avatar list
            $avatars = Pi::service('avatar')->getList($uidList, 'small');

            // Get credit list
            $creditList = Pi::api('credit', 'order')->getCreditList($uidList);

            // Get invoice
            $invoices     = Pi::api('invoice', 'order')->getInvoiceFromUser($uidList);
            $invoiceCount = count($invoices);

            $orderList                 = [];
            $orderTotal                = 0;
            $invoicePaidList           = [];
            $invoicePaidTotal          = 0;
            $invoiceUnPaidList         = [];
            $invoiceUnPaidTotal        = 0;
            $invoiceDelayedList        = [];
            $invoiceDelayedTotal       = 0;
            $invoiceNextList           = [];
            $invoiceNextTotal          = 0;
            $invoiceUnPaidDelayedTotal = 0;
            $lastInvoice               = [];
            $chart                     = [];
            $chartCreate               = [];
            $chartTime                 = [];
            $chartPaid                 = [];
            $chartLabel                = [];
            $nextPaid                  = 60 * 60 * 24 * 11;

            foreach ($users as $user) {
                $user['url']                       = Pi::url(Pi::service('user')->getUrl('profile', $user['id']));
                $user['avatar']                    = $avatars[$user['id']];
                $user['credit']                    = $creditList[$user['id']];
                $user['invoice']                   = [];
                $user['orderList']                 = [];
                $user['orderTotal']                = 0;
                $user['invoicePaidList']           = [];
                $user['invoicePaidTotal']          = 0;
                $user['invoiceUnPaidList']         = [];
                $user['invoiceUnPaidTotal']        = 0;
                $user['invoiceDelayedList']        = [];
                $user['invoiceDelayedTotal']       = 0;
                $user['invoiceNextPaidList']       = [];
                $user['invoiceNextPaidTotal']      = 0;
                $user['invoiceUnPaidDelayedTotal'] = 0;
                $user['lastInvoice']               = [];
                $user['url_list_user']             = Pi::url($this->url('admin', [
                    'module'     => 'order',
                    'controller' => 'order',
                    'action'     => 'listUser',
                    'uid'        => $user['id'],
                ]));

                foreach ($invoices as $invoice) {
                    if ($invoice['uid'] == $user['id']) {
                        $user['invoice'][$invoice['id']] = $invoice;

                        if ($invoice['time_payment'] > 0) {
                            $user['invoicePaidList'][$invoice['id']] = $invoice['id'];
                            $user['invoicePaidTotal']                = $user['invoicePaidTotal'] + $invoice['total_price'];
                        }

                        if ($invoice['time_payment'] == 0) {
                            $user['invoiceUnPaidList'][$invoice['id']] = $invoice['id'];
                            $user['invoiceUnPaidTotal']                = $user['invoiceUnPaidTotal'] + $invoice['total_price'];
                        }

                        if ($invoice['time_duedate'] < time() && $invoice['time_payment'] == 0) {
                            $user['invoiceDelayedList'][$invoice['id']] = $invoice['id'];
                            $user['invoiceDelayedTotal']                = $user['invoiceDelayedTotal'] + $invoice['total_price'];
                        }

                        if ($invoice['time_duedate'] < (time() + $nextPaid) && $invoice['time_payment'] == 0) {
                            $user['invoiceNextList'][$invoice['id']] = $invoice['id'];
                            $user['invoiceNextTotal']                = $user['invoiceNextTotal'] + $invoice['total_price'];
                        }

                        $user['orderTotal']                   = $user['orderTotal'] + $invoice['total_price'];
                        $user['orderList'][$invoice['order']] = $invoice['order'];

                        $user['lastInvoice'][$invoice['time_duedate']] = $invoice['time_duedate'];
                        $lastInvoice[$invoice['time_duedate']]         = $invoice['time_duedate'];

                        // Set chart
                        $chartCreate[date('Y/m', $invoice['time_create'])]++;
                        $chartTime[date('Y/m', $invoice['time_duedate'])]++;
                        $chartLabel[date('Y/m', $invoice['time_create'])] = date('Y/m', $invoice['time_create']);
                        if ($invoice['time_payment'] > 0) {
                            $chartPaid[date('Y/m', $invoice['time_payment'])]++;
                            $chartLabel[date('Y/m', $invoice['time_payment'])] = date('Y/m', $invoice['time_payment']);
                        }

                    }
                }

                $user['invoiceUnPaidDelayedTotal'] = $user['invoiceUnPaidTotal'] - $user['invoiceDelayedTotal'];

                if (!empty($user['lastInvoice'])) {
                    $user['lastInvoice'] = max($user['lastInvoice']);
                    $user['lastInvoice'] = _date($user['lastInvoice'], ['pattern' => 'yyyy/MM/dd']);
                } else {
                    $user['lastInvoice'] = '';
                }

                $orderTotal                   = $orderTotal + $user['orderTotal'];
                $orderList                    = array_unique(array_merge($orderList, $user['orderList']));
                $invoicePaidList              = array_unique(array_merge($invoicePaidList, $user['invoicePaidList']));
                $invoicePaidTotal             = $invoicePaidTotal + $user['invoicePaidTotal'];
                $invoiceUnPaidList            = array_unique(array_merge($invoiceUnPaidList, $user['invoiceUnPaidList']));
                $invoiceUnPaidTotal           = $invoiceUnPaidTotal + $user['invoiceUnPaidTotal'];
                $invoiceDelayedList           = array_unique(array_merge($invoiceDelayedList, $user['invoiceDelayedList']));
                $invoiceDelayedTotal          = $invoiceDelayedTotal + $user['invoiceDelayedTotal'];
                $invoiceNextList              = array_unique(array_merge($invoiceNextList, $user['invoiceNextList']));
                $invoiceNextTotal             = $invoiceNextTotal + $user['invoiceNextTotal'];
                $invoiceUnPaidDelayedTotal    = $invoiceUnPaidDelayedTotal + $user['invoiceUnPaidDelayedTotal'];
                $userInformation[$user['id']] = $user;
            }

            // Set last time
            $lastInvoice = max($lastInvoice);
            $lastInvoice = _date($lastInvoice, ['pattern' => 'yyyy/MM/dd']);

            // Chart
            ksort($chartLabel);
            foreach ($chartLabel as $key => $value) {
                $chart['label'][] = $value;
                if (isset($chartCreate[$key])) {
                    $chart['create'][] = $chartCreate[$key];
                } else {
                    $chart['create'][] = 0;
                }
                if (isset($chartPaid[$key])) {
                    $chart['paid'][] = $chartPaid[$key];
                } else {
                    $chart['paid'][] = 0;
                }
                if (isset($chartTime[$key])) {
                    $chart['time'][] = $chartTime[$key];
                } else {
                    $chart['time'][] = 0;
                }
            }
            $chart['label']  = json_encode($chart['label']);
            $chart['create'] = json_encode($chart['create']);
            $chart['paid']   = json_encode($chart['paid']);
            $chart['time']   = json_encode($chart['time']);

            // Set view
            $this->view()->assign('company', $company);
            $this->view()->assign('userInformation', $userInformation);
            $this->view()->assign('uidList', $uidList);
            $this->view()->assign('creditList', $creditList);
            $this->view()->assign('invoices', $invoices);
            $this->view()->assign('invoiceCount', $invoiceCount);
            $this->view()->assign('orderTotal', $orderTotal);
            $this->view()->assign('orderList', $orderList);
            $this->view()->assign('invoicePaidList', $invoicePaidList);
            $this->view()->assign('invoicePaidTotal', $invoicePaidTotal);
            $this->view()->assign('invoiceUnPaidList', $invoiceUnPaidList);
            $this->view()->assign('invoiceUnPaidTotal', $invoiceUnPaidTotal);
            $this->view()->assign('invoiceDelayedList', $invoiceDelayedList);
            $this->view()->assign('invoiceDelayedTotal', $invoiceDelayedTotal);
            $this->view()->assign('invoiceNextList', $invoiceNextList);
            $this->view()->assign('invoiceNextTotal', $invoiceNextTotal);
            $this->view()->assign('invoiceUnPaidDelayedTotal', $invoiceUnPaidDelayedTotal);
            $this->view()->assign('lastInvoice', $lastInvoice);
            $this->view()->assign('chart', $chart);
        }


        // Set view
        $this->view()->setTemplate('analytic-company');
        $this->view()->assign('config', $config);
        $this->view()->assign('systemRoles', $systemRoles);
        $this->view()->assign('role', $role);
    }

    public function userAction()
    {
        // Get read
        $uid = $this->params('uid', 0);

        // Get config
        $config = Pi::service('registry')->config->read($this->getModule());

        // Check is user
        if ($uid > 0) {

            // Set list
            $list = [];

            // Select total order
            $columns = [
                'sum'   => new Expression('sum(total_price)'),
                'count' => new Expression('count(*)'),
            ];
            $where   = ['status_order' => [1, 2, 3, 7], 'uid' => $uid];
            $select  = Pi::model('order', 'order')->select()->columns($columns)->where($where);
            $rowset  = Pi::model('order', 'order')->selectWith($select);
            foreach ($rowset as $row) {
                $list['order']['sum']   = $row->sum;
                $list['order']['count'] = $row->count;
            }

            // Select total invoice
            $columns = [
                'sum'   => new Expression('sum(total_price)'),
                'count' => new Expression('count(*)'),
            ];
            $where   = ['status' => [1, 2], 'uid' => $uid];
            $select  = Pi::model('invoice', 'order')->select()->columns($columns)->where($where);
            $rowset  = Pi::model('invoice', 'order')->selectWith($select);
            foreach ($rowset as $row) {
                $list['total']['sum']   = $row->sum;
                $list['total']['count'] = $row->count;
            }

            // Select paid invoice
            $columns = [
                'sum'   => new Expression('sum(total_price)'),
                'count' => new Expression('count(*)'),
            ];
            $where   = ['status' => [1, 2], 'time_payment > ?' => 0, 'uid' => $uid];
            $select  = Pi::model('invoice', 'order')->select()->columns($columns)->where($where);
            $rowset  = Pi::model('invoice', 'order')->selectWith($select);
            foreach ($rowset as $row) {
                $list['paid']['sum']   = $row->sum;
                $list['paid']['count'] = $row->count;
            }

            // Select unpaid invoice
            $columns = [
                'sum'   => new Expression('sum(total_price)'),
                'count' => new Expression('count(*)'),
            ];
            $where   = ['status' => [1, 2], 'time_payment' => 0, 'uid' => $uid];
            $select  = Pi::model('invoice', 'order')->select()->columns($columns)->where($where);
            $rowset  = Pi::model('invoice', 'order')->selectWith($select);
            foreach ($rowset as $row) {
                $list['unPaid']['sum']   = $row->sum;
                $list['unPaid']['count'] = $row->count;
            }

            // Select unpaid invoice
            $columns = [
                'sum'   => new Expression('sum(total_price)'),
                'count' => new Expression('count(*)'),
            ];
            $where   = ['status' => [1, 2], 'time_payment' => 0, 'time_duedate < ?' => time(), 'uid' => $uid];
            $select  = Pi::model('invoice', 'order')->select()->columns($columns)->where($where);
            $rowset  = Pi::model('invoice', 'order')->selectWith($select);
            foreach ($rowset as $row) {
                $list['delayed']['sum']   = $row->sum;
                $list['delayed']['count'] = $row->count;
            }

            // Get user
            $user = Pi::service('user')->get($uid,
                ['uid', 'name', 'active', 'first_name', 'last_name', 'email']
            );

            // Get credit list
            $credit = Pi::api('credit', 'order')->getCredit($uid);

            // Get order details
            $orderList = [];
            $orderIds = [];
            $where     = ['status_order' => [1, 2, 3, 7], 'uid' => $uid];
            $select    = Pi::model('order', 'order')->select()->where($where);
            $rowset    = Pi::model('order', 'order')->selectWith($select);
            foreach ($rowset as $row) {
                $orderIds[$row->id] = $row->id;
                $orderList[$row->id]             = Pi::api('order', 'order')->canonizeOrder($row, $user);
                $orderList[$row->id]['products'] = Pi::api('order', 'order')->listProduct($row->id, $row->module_name);
            }

            // Get invoice
            $invoices    = Pi::api('invoice', 'order')->getInvoiceFromUser($uid, false, $orderIds);
            $lastInvoice = [];
            $chart       = [];
            $chartCreate = [];
            $chartTime   = [];
            $chartPaid   = [];
            $chartLabel  = [];
            $nextPaid    = 60 * 60 * 24 * 11;

            // Set user
            $user['url']                       = Pi::url(Pi::service('user')->getUrl('profile', $user['id']));
            $user['avatar']                    = Pi::user()->avatar($user['id'], 'medium', [
                'alt'   => '',
                'class' => 'img-circle',
            ]);
            $user['credit']                    = $credit;
            $user['invoice']                   = [];
            $user['orderList']                 = [];
            $user['orderTotal']                = 0;
            $user['invoicePaidList']           = [];
            $user['invoicePaidTotal']          = 0;
            $user['invoiceUnPaidList']         = [];
            $user['invoiceUnPaidTotal']        = 0;
            $user['invoiceDelayedList']        = [];
            $user['invoiceDelayedTotal']       = 0;
            $user['invoiceNextPaidList']       = [];
            $user['invoiceNextPaidTotal']      = 0;
            $user['invoiceUnPaidDelayedTotal'] = 0;
            $user['lastInvoice']               = [];
            $user['url_list_user']             = Pi::url($this->url('admin', [
                'module'     => 'order',
                'controller' => 'order',
                'action'     => 'listUser',
                'uid'        => $user['id'],
            ]));

            foreach ($invoices as $invoice) {
                if ($invoice['uid'] == $user['id']) {
                    $user['invoice'][$invoice['id']] = $invoice;

                    if ($invoice['time_payment'] > 0) {
                        $user['invoicePaidList'][$invoice['id']] = $invoice['id'];
                        $user['invoicePaidTotal']                = $user['invoicePaidTotal'] + $invoice['total_price'];
                    }

                    if ($invoice['time_payment'] == 0) {
                        $user['invoiceUnPaidList'][$invoice['id']] = $invoice['id'];
                        $user['invoiceUnPaidTotal']                = $user['invoiceUnPaidTotal'] + $invoice['total_price'];
                    }

                    if ($invoice['time_duedate'] < time() && $invoice['time_payment'] == 0) {
                        $user['invoiceDelayedList'][$invoice['id']] = $invoice['id'];
                        $user['invoiceDelayedTotal']                = $user['invoiceDelayedTotal'] + $invoice['total_price'];
                    }

                    if ($invoice['time_duedate'] < (time() + $nextPaid) && $invoice['time_payment'] == 0) {
                        $user['invoiceNextList'][$invoice['id']] = $invoice['id'];
                        $user['invoiceNextTotal']                = $user['invoiceNextTotal'] + $invoice['total_price'];
                    }

                    $user['orderTotal']                   = $user['orderTotal'] + $invoice['total_price'];
                    $user['orderList'][$invoice['order']] = $invoice['order'];

                    $user['lastInvoice'][$invoice['time_duedate']] = $invoice['time_duedate'];
                    $lastInvoice[$invoice['time_duedate']]         = $invoice['time_duedate'];

                    // Set chart
                    $chartCreate[date('Y/m', $invoice['time_create'])]++;
                    $chartTime[date('Y/m', $invoice['time_duedate'])]++;
                    $chartLabel[date('Y/m', $invoice['time_create'])] = date('Y/m', $invoice['time_create']);
                    if ($invoice['time_payment'] > 0) {
                        $chartPaid[date('Y/m', $invoice['time_payment'])]++;
                        $chartLabel[date('Y/m', $invoice['time_payment'])] = date('Y/m', $invoice['time_payment']);
                    }

                }
            }

            $user['invoiceUnPaidDelayedTotal'] = $user['invoiceUnPaidTotal'] - $user['invoiceDelayedTotal'];

            if (!empty($user['lastInvoice'])) {
                $user['lastInvoice'] = max($user['lastInvoice']);
                $user['lastInvoice'] = _date($user['lastInvoice'], ['pattern' => 'yyyy/MM/dd']);
            } else {
                $user['lastInvoice'] = '';
            }

            $installment = Pi::api('installment', 'order')->blockTable($user, $orderIds, $invoices);

            // Chart
            ksort($chartLabel);
            foreach ($chartLabel as $key => $value) {
                $chart['label'][] = $value;
                if (isset($chartCreate[$key])) {
                    $chart['create'][] = $chartCreate[$key];
                } else {
                    $chart['create'][] = 0;
                }
                if (isset($chartPaid[$key])) {
                    $chart['paid'][] = $chartPaid[$key];
                } else {
                    $chart['paid'][] = 0;
                }
                if (isset($chartTime[$key])) {
                    $chart['time'][] = $chartTime[$key];
                } else {
                    $chart['time'][] = 0;
                }
            }
            $chart['label']  = json_encode($chart['label']);
            $chart['create'] = json_encode($chart['create']);
            $chart['paid']   = json_encode($chart['paid']);
            $chart['time']   = json_encode($chart['time']);


            // Set view
            $this->view()->assign('list', $list);
            $this->view()->assign('user', $user);
            $this->view()->assign('chart', $chart);
            $this->view()->assign('orderList', $orderList);
            $this->view()->assign('installment', $installment);
        } else {
            $uidList = [];
            $columns = ['uid'];
            $where   = ['status_order' => [1, 2, 3, 7]];
            $order   = ['first_name ASC'];
            $group   = ['uid'];
            $select  = Pi::model('order', 'order')->select()->columns($columns)->where($where)->order($order)->group($group);
            $rowset  = Pi::model('order', 'order')->selectWith($select);
            foreach ($rowset as $row) {
                $uidList[$row->uid] = $row->uid;
            }

            // Get avatar list
            $avatars = Pi::service('avatar')->getList($uidList, 'small');

            // Get user list
            $users    = [];
            $userList = Pi::service('user')->mget($uidList,
                ['uid', 'name', 'active', 'first_name', 'last_name', 'email']
            );
            foreach ($userList as $userSingle) {
                $userSingle['id']          = $userSingle['uid'];
                $userSingle['avatar']      = $avatars[$userSingle['uid']];
                $userSingle['view_url']    = $this->url('', ['action' => 'user', 'uid' => $userSingle['uid']]);
                $users[$userSingle['uid']] = $userSingle;
            }

            // Set view
            $this->view()->assign('uidList', $uidList);
            $this->view()->assign('users', $users);
        }

        // Set view
        $this->view()->setTemplate('analytic-user');
        $this->view()->assign('config', $config);
        $this->view()->assign('uid', $uid);
    }
}