<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt New BSD License
 */

/**
 * @author Hossein Azizabadi <azizabadi@faragostaresh.com>
 */

namespace Module\Analytic\Controller\Front;

use Pi;
use Pi\Mvc\Controller\ActionController;
use Zend\Db\Sql\Predicate\Expression;

class IndexController extends ActionController
{
    public function indexAction()
    {
        // Check user is login or not
        Pi::service('authentication')->requireLogin();

        // Get config
        $config = Pi::service('registry')->config->read($this->getModule());

        // Get system roles
        $systemRoles = Pi::registry('role')->read();

        // Get user roles
        $uid         = Pi::user()->getId();
        $roles       = Pi::service('user')->getRole($uid, 'front');
        $roleList    = [];
        $companyList = [];
        foreach ($roles as $role) {
            if (!in_array($role, ['webmaster', 'member'])) {
                $roleList[$role] = $role;
                if ($role != $config['panel_admin']) {
                    $companyList[$role] = $role;
                }
            }
        }

        // Check access
        if (!in_array($config['panel_admin'], $roleList)) {
            // Jump
            $message = __('You are not access to panel');
            $this->jump(['controller' => 'index', 'action' => 'index'], $message);
        }

        // Check company
        if (empty($companyList)) {
            // Jump
            $message = __('You company information not set');
            $this->jump(['controller' => 'index', 'action' => 'index'], $message);
        }


        // Get uid list
        $model   = Pi::model('user_role');
        $select  = $model->select()->where(['role' => $roleList]);
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
        ksort($users);

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
            $user['url_list_user']             = Pi::url($this->url('', [
                'controller' => 'index',
                'action'     => 'user',
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

        // require persian date class
        require_once Pi::path('module') . '/order/src/Api/pdate.php';

        // Set array
        $key1         = sprintf('%s/%s', pdate('m', strtotime('this month')), pdate('Y', strtotime('this month')));
        $key2         = sprintf('%s/%s', pdate('m', strtotime('+1 month')), pdate('Y', strtotime('+1 month')));
        $key3         = sprintf('%s/%s', pdate('m', strtotime('+2 month')), pdate('Y', strtotime('+2 month')));
        $key4         = sprintf('%s/%s', pdate('m', strtotime('+3 month')), pdate('Y', strtotime('+3 month')));
        $key5         = sprintf('%s/%s', pdate('m', strtotime('+4 month')), pdate('Y', strtotime('+4 month')));
        $key6         = sprintf('%s/%s', pdate('m', strtotime('+5 month')), pdate('Y', strtotime('+5 month')));
        $key7         = sprintf('%s/%s', pdate('m', strtotime('+6 month')), pdate('Y', strtotime('+6 month')));
        $key8         = sprintf('%s/%s', pdate('m', strtotime('+7 month')), pdate('Y', strtotime('+7 month')));
        $key9         = sprintf('%s/%s', pdate('m', strtotime('+8 month')), pdate('Y', strtotime('+8 month')));
        $key10        = sprintf('%s/%s', pdate('m', strtotime('+9 month')), pdate('Y', strtotime('+9 month')));
        $key11        = sprintf('%s/%s', pdate('m', strtotime('+10 month')), pdate('Y', strtotime('+10 month')));
        $key12        = sprintf('%s/%s', pdate('m', strtotime('+11 month')), pdate('Y', strtotime('+11 month')));
        $month1       = pmktime(0, 0, 0, pdate('m', strtotime('this month')), 1, pdate('Y', strtotime('this month')));
        $month2       = pmktime(0, 0, 0, pdate('m', strtotime('+1 month')), 1, pdate('Y', strtotime('+1 month')));
        $month3       = pmktime(0, 0, 0, pdate('m', strtotime('+2 month')), 1, pdate('Y', strtotime('+2 month')));
        $month4       = pmktime(0, 0, 0, pdate('m', strtotime('+3 month')), 1, pdate('Y', strtotime('+3 month')));
        $month5       = pmktime(0, 0, 0, pdate('m', strtotime('+4 month')), 1, pdate('Y', strtotime('+4 month')));
        $month6       = pmktime(0, 0, 0, pdate('m', strtotime('+5 month')), 1, pdate('Y', strtotime('+5 month')));
        $month7       = pmktime(0, 0, 0, pdate('m', strtotime('+6 month')), 1, pdate('Y', strtotime('+6 month')));
        $month8       = pmktime(0, 0, 0, pdate('m', strtotime('+7 month')), 1, pdate('Y', strtotime('+7 month')));
        $month9       = pmktime(0, 0, 0, pdate('m', strtotime('+8 month')), 1, pdate('Y', strtotime('+8 month')));
        $month10      = pmktime(0, 0, 0, pdate('m', strtotime('+9 month')), 1, pdate('Y', strtotime('+9 month')));
        $month11      = pmktime(0, 0, 0, pdate('m', strtotime('+10 month')), 1, pdate('Y', strtotime('+10 month')));
        $month12      = pmktime(0, 0, 0, pdate('m', strtotime('+11 month')), 1, pdate('Y', strtotime('+11 month')));
        $month13      = pmktime(0, 0, 0, pdate('m', strtotime('+12 month')), 1, pdate('Y', strtotime('+12 month')));
        $chart['next'] = [
            'count' => [
                $key1 => 0, $key2 => 0, $key3 => 0, $key4 => 0, $key5 => 0, $key6 => 0, $key7 => 0,
                $key8 => 0, $key9 => 0, $key10 => 0, $key11 => 0, $key12 => 0,
            ],
            'sum'   => [
                $key1 => 0, $key2 => 0, $key3 => 0, $key4 => 0, $key5 => 0, $key6 => 0, $key7 => 0,
                $key8 => 0, $key9 => 0, $key10 => 0, $key11 => 0, $key12 => 0,
            ],
        ];

        // Select next days
        foreach ($invoices as $invoice) {
            if ($invoice['time_duedate'] >= $month1 && $invoice['time_duedate'] < $month2) {
                $chart['next']['count'][$key1]++;
                $chart['next']['sum'][$key1] = $chart['next']['sum'][$key1] + $invoice['total_price'];
            } elseif ($invoice['time_duedate'] >= $month2 && $invoice['time_duedate'] < $month3) {
                $chart['next']['count'][$key2]++;
                $chart['next']['sum'][$key2] = $chart['next']['sum'][$key2] + $invoice['total_price'];
            } elseif ($invoice['time_duedate'] >= $month3 && $invoice['time_duedate'] < $month4) {
                $chart['next']['count'][$key3]++;
                $chart['next']['sum'][$key3] = $chart['next']['sum'][$key3] + $invoice['total_price'];
            } elseif ($invoice['time_duedate'] >= $month4 && $invoice['time_duedate'] < $month5) {
                $chart['next']['count'][$key4]++;
                $chart['next']['sum'][$key4] = $chart['next']['sum'][$key4] + $invoice['total_price'];
            } elseif ($invoice['time_duedate'] >= $month5 && $invoice['time_duedate'] < $month6) {
                $chart['next']['count'][$key5]++;
                $chart['next']['sum'][$key5] = $chart['next']['sum'][$key5] + $invoice['total_price'];
            } elseif ($invoice['time_duedate'] >= $month6 && $invoice['time_duedate'] < $month7) {
                $chart['next']['count'][$key6]++;
                $chart['next']['sum'][$key6] = $chart['next']['sum'][$key6] + $invoice['total_price'];
            } elseif ($invoice['time_duedate'] >= $month7 && $invoice['time_duedate'] < $month8) {
                $chart['next']['count'][$key7]++;
                $chart['next']['sum'][$key7] = $chart['next']['sum'][$key7] + $invoice['total_price'];
            } elseif ($invoice['time_duedate'] >= $month8 && $invoice['time_duedate'] < $month9) {
                $chart['next']['count'][$key8]++;
                $chart['next']['sum'][$key8] = $chart['next']['sum'][$key8] + $invoice['total_price'];
            } elseif ($invoice['time_duedate'] >= $month9 && $invoice['time_duedate'] < $month10) {
                $chart['next']['count'][$key9]++;
                $chart['next']['sum'][$key9] = $chart['next']['sum'][$key9] + $invoice['total_price'];
            } elseif ($invoice['time_duedate'] >= $month10 && $invoice['time_duedate'] < $month11) {
                $chart['next']['count'][$key10]++;
                $chart['next']['sum'][$key10] = $chart['next']['sum'][$key10] + $invoice['total_price'];
            } elseif ($invoice['time_duedate'] >= $month11 && $invoice['time_duedate'] < $month12) {
                $chart['next']['count'][$key11]++;
                $chart['next']['sum'][$key11] = $chart['next']['sum'][$key11] + $invoice['total_price'];
            } elseif ($invoice['time_duedate'] >= $month12 && $invoice['time_duedate'] < $month13) {
                $chart['next']['count'][$key12]++;
                $chart['next']['sum'][$key12] = $chart['next']['sum'][$key12] + $invoice['total_price'];
            }
        }

        // Set title
        $title = [];
        foreach ($companyList as $company) {
            $title[$company] = $systemRoles[$company]['title'];
        }

        // Set view
        $this->view()->setTemplate('analytic-panel');
        $this->view()->assign('config', $config);
        $this->view()->assign('roleList', $roleList);
        $this->view()->assign('companyList', $companyList);
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
        $this->view()->assign('title', $title);
        $this->view()->assign('chart', $chart);
    }

    public function userAction()
    {
        // Check user is login or not
        Pi::service('authentication')->requireLogin();

        // Get config
        $config = Pi::service('registry')->config->read($this->getModule());

        // Get read
        $uid = $this->params('uid');

        // Get company information
        if ($uid) {
            $companyUid  = Pi::user()->getId();
            $roles       = Pi::service('user')->getRole($companyUid, 'front');
            $roleList    = [];
            $companyList = [];
            foreach ($roles as $role) {
                if (!in_array($role, ['webmaster', 'member'])) {
                    $roleList[$role] = $role;
                    if ($role != $config['panel_admin']) {
                        $companyList[$role] = $role;
                    }
                }
            }

            // Check access
            if (!in_array($config['panel_admin'], $roleList)) {
                return false;
            }

            // Check company
            if (empty($companyList)) {
                return false;
            }

            $viewer = 'company';
        } else {
            $uid = Pi::user()->getId();
            $viewer = 'user';
        }

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
        $orderIds  = [];
        $where     = ['status_order' => [1, 2, 3, 7], 'uid' => $uid];
        $select    = Pi::model('order', 'order')->select()->where($where);
        $rowset    = Pi::model('order', 'order')->selectWith($select);
        foreach ($rowset as $row) {
            $orderIds[$row->id]              = $row->id;
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
            'class' => 'rounded-circle',
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
        $this->view()->setTemplate('analytic-user');
        $this->view()->assign('config', $config);
        $this->view()->assign('uid', $uid);
        $this->view()->assign('list', $list);
        $this->view()->assign('user', $user);
        $this->view()->assign('chart', $chart);
        $this->view()->assign('orderList', $orderList);
        $this->view()->assign('installment', $installment);
        $this->view()->assign('viewer', $viewer);
    }
}