<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/lib/admin/Dashboard.php';

$dashboard = new Dashboard();
$message = $dashboard->getMessage();
$error = $dashboard->getError();
$articles = $dashboard->getArticles();
$subscriptions = $dashboard->getSubscriptions();
$orders = $dashboard->getOrders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creatim Admin Panel</title>
    <link rel="stylesheet" href="styles/dashboard.css">
</head>
<body>
    <div class="o-container">
        <h1>🎨 Creatim Admin Panel</h1>
        
        <?php if ($message): ?>
            <div class="c-alert c-alert--success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="c-alert c-alert--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Articles Section -->
        <div class="section">
            <h2>Articles Management</h2>
            
            <!-- Article Form -->
            <form method="POST" class="c-form c-form--inline" id="articleForm">
                <input type="hidden" name="action" id="articleAction" value="add_article">
                <input type="hidden" name="id" id="articleId">
                
                <div class="c-form__inputs">
                    <div class="c-form__group">
                        <label class="c-form__label">Name *</label>
                        <input type="text" name="name" id="articleName" class="c-form__input" required>
                    </div>
                    
                    <div class="c-form__group">
                        <label class="c-form__label">Description *</label>
                        <textarea name="description" id="articleDescription" class="c-form__textarea" required></textarea>
                    </div>
                    
                    <div class="c-form__group">
                        <label class="c-form__label">Price (€) *</label>
                        <input type="number" step="0.01" min="0" name="price_euro" id="articlePriceEuro" class="c-form__input" required placeholder="12.99">
                        <input type="hidden" name="price" id="articlePrice">
                    </div>
                    
                    <div class="c-form__group">
                        <label class="c-form__label">Supplier Email *</label>
                        <input type="email" name="supplier_email" id="articleEmail" class="c-form__input" required>
                    </div>
                </div>
                
                <div class="c-form__buttons">
                    <button type="button" class="c-btn c-btn--secondary" onclick="resetArticleForm()">Clear</button>
                    <button type="submit" class="c-btn c-btn--primary">Save Article</button>
                </div>
            </form>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Supplier Email</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($articles)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No articles found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($articles as $article): ?>
                    <tr>
                        <td><?= $article['id'] ?></td>
                        <td><?= htmlspecialchars($article['name']) ?></td>
                        <td><?= htmlspecialchars(substr($article['description'], 0, 50)) ?>...</td>
                        <td><span class="c-badge c-badge--price">€<?= number_format($article['price'] / 100, 2) ?></span></td>
                        <td><?= htmlspecialchars($article['supplier_email']) ?></td>
                        <td><?= $article['date_created'] ?></td>
                        <td>
                            <button class="c-btn c-btn--info" onclick='editArticle(<?= json_encode($article) ?>, this)'>✏️</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_article">
                                <input type="hidden" name="id" value="<?= $article['id'] ?>">
                                <button type="submit" class="c-btn c-btn--danger" onclick="return confirm('Delete this article?')">❌</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Subscriptions Section -->
        <div class="section">
            <h2>Subscriptions Management</h2>
            
            <!-- Subscription Form -->
            <form method="POST" class="c-form c-form--inline" id="subscriptionForm">
                <input type="hidden" name="action" id="subscriptionAction" value="add_subscription">
                <input type="hidden" name="id" id="subscriptionId">
                
                <div class="c-form__inputs">
                    <div class="c-form__group">
                        <label class="c-form__label">Description *</label>
                        <textarea name="description" id="subscriptionDescription" class="c-form__textarea" required></textarea>
                    </div>
                    
                    <div class="c-form__group">
                        <label class="c-form__label">Price (€) *</label>
                        <input type="number" step="0.01" min="0" name="price_euro" id="subscriptionPriceEuro" class="c-form__input" required placeholder="29.99">
                        <input type="hidden" name="price" id="subscriptionPrice">
                    </div>
                    
                    <div class="c-form__group">
                        <label class="c-form__label">Type *</label>
                        <select name="physical" id="subscriptionPhysical" class="c-form__select" required>
                            <option value="0">Digital</option>
                            <option value="1">Physical</option>
                        </select>
                    </div>
                </div>
                
                <div class="c-form__buttons">
                    <button type="button" class="c-btn c-btn--secondary" onclick="resetSubscriptionForm()">Clear</button>
                    <button type="submit" class="c-btn c-btn--primary">Save Subscription</button>
                </div>
            </form>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Type</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscriptions)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No subscriptions found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($subscriptions as $subscription): ?>
                    <tr>
                        <td><?= $subscription['id'] ?></td>
                        <td><?= htmlspecialchars($subscription['description']) ?></td>
                        <td><span class="c-badge c-badge--price">€<?= number_format($subscription['price'] / 100, 2) ?></span></td>
                        <td><?= $subscription['physical'] ? '📦 Physical' : '💻 Digital' ?></td>
                        <td><?= $subscription['date_created'] ?></td>
                        <td>
                            <button class="c-btn c-btn--info" onclick='editSubscription(<?= json_encode($subscription) ?>, this)'>✏️</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_subscription">
                                <input type="hidden" name="id" value="<?= $subscription['id'] ?>">
                                <button type="submit" class="c-btn c-btn--danger" onclick="return confirm('Delete this subscription?')">❌</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Orders Section -->
        <div class="section">
            <h2>Orders Management</h2>
            
            <!-- Order Form -->
            <form method="POST" class="c-form c-form--inline" id="orderForm">
                <input type="hidden" name="action" id="orderAction" value="add_order">
                <input type="hidden" name="id" id="orderId">
                
                <div class="c-form__inputs">
                    <div class="c-form__group">
                        <label class="c-form__label">Order Number *</label>
                        <input type="number" name="order_number" id="orderNumber" class="c-form__input" required>
                    </div>
                    
                    <div class="c-form__group">
                        <label class="c-form__label">Customer Phone *</label>
                        <input type="text" name="customer_phone" id="orderPhone" class="c-form__input">
                    </div>
                    
                    <div class="c-form__group">
                        <label class="c-form__label">Status *</label>
                        <select name="status" id="orderStatus" class="c-form__select" required>
                            <option value="NEEDS TO BE PROCESSED">NEEDS TO BE PROCESSED</option>
                            <option value="PROCESSED">PROCESSED</option>
                        </select>
                    </div>
                    
                    <div class="c-form__group">
                        <label class="c-form__label">Price (€) *</label>
                        <input type="number" step="0.01" min="0" name="price_euro" id="orderPriceEuro" class="c-form__input" required placeholder="49.99">
                        <input type="hidden" name="price" id="orderPrice">
                    </div>
                    
                    <div class="c-form__group">
                        <label class="c-form__label">Articles</label>
                        <textarea name="articles" id="orderArticles" class="c-form__textarea"></textarea>
                    </div>
                    
                    <div class="c-form__group">
                        <label class="c-form__label">Subscription Package</label>
                        <input type="text" name="subscription_pkg" id="orderSubscription" class="c-form__input">
                    </div>
                </div>
                
                <div class="c-form__buttons">
                    <button type="button" class="c-btn c-btn--secondary" onclick="resetOrderForm()">Clear</button>
                    <button type="submit" class="c-btn c-btn--primary">Save Order</button>
                </div>
            </form>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Order #</th>
                        <th>Customer Phone</th>
                        <th>Status</th>
                        <th>Price</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No orders found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= $order['id'] ?></td>
                        <td><?= $order['order_number'] ?></td>
                        <td><?= $order['customer_phone'] ?></td>
                        <td><span class="c-badge c-badge--status-<?= strtolower($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></td>
                        <td><span class="c-badge c-badge--price">€<?= number_format($order['price'] / 100, 2) ?></span></td>
                        <td><?= $order['date_created'] ?></td>
                        <td>
                            <button class="c-btn c-btn--info" onclick='editOrder(<?= json_encode($order) ?>, this)'>✏️</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_order">
                                <input type="hidden" name="id" value="<?= $order['id'] ?>">
                                <button type="submit" class="c-btn c-btn--danger" onclick="return confirm('Delete this order?')">❌</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Convert euros to cents
        function eurosToCents(euros) {
            return Math.round(parseFloat(euros) * 100);
        }
        
        // Convert cents to euros
        function centsToEuros(cents) {
            return (parseInt(cents) / 100).toFixed(2);
        }
        
        // Remove all row highlights
        function clearHighlights() {
            document.querySelectorAll('tr.row-highlighted').forEach(row => {
                row.classList.remove('row-highlighted');
            });
        }
        
        // Article Functions
        function editArticle(article, button) {
            clearHighlights();
            button.closest('tr').classList.add('row-highlighted');
            
            document.getElementById('articleAction').value = 'edit_article';
            document.getElementById('articleId').value = article.id;
            document.getElementById('articleName').value = article.name;
            document.getElementById('articleDescription').value = article.description;
            document.getElementById('articlePriceEuro').value = centsToEuros(article.price);
            document.getElementById('articleEmail').value = article.supplier_email;
            
            document.getElementById('articleForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        function resetArticleForm() {
            clearHighlights();
            document.getElementById('articleForm').reset();
            document.getElementById('articleAction').value = 'add_article';
            document.getElementById('articleId').value = '';
        }
        
        // Convert price before article form submission
        document.getElementById('articleForm').addEventListener('submit', function(e) {
            const euroValue = document.getElementById('articlePriceEuro').value;
            document.getElementById('articlePrice').value = eurosToCents(euroValue);
        });
        
        // Subscription Functions
        function editSubscription(subscription, button) {
            clearHighlights();
            button.closest('tr').classList.add('row-highlighted');
            
            document.getElementById('subscriptionAction').value = 'edit_subscription';
            document.getElementById('subscriptionId').value = subscription.id;
            document.getElementById('subscriptionDescription').value = subscription.description;
            document.getElementById('subscriptionPriceEuro').value = centsToEuros(subscription.price);
            document.getElementById('subscriptionPhysical').value = subscription.physical;
            
            document.getElementById('subscriptionForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        function resetSubscriptionForm() {
            clearHighlights();
            document.getElementById('subscriptionForm').reset();
            document.getElementById('subscriptionAction').value = 'add_subscription';
            document.getElementById('subscriptionId').value = '';
        }
        
        // Convert price before subscription form submission
        document.getElementById('subscriptionForm').addEventListener('submit', function(e) {
            const euroValue = document.getElementById('subscriptionPriceEuro').value;
            document.getElementById('subscriptionPrice').value = eurosToCents(euroValue);
        });
        
        // Order Functions
        function editOrder(order, button) {
            clearHighlights();
            button.closest('tr').classList.add('row-highlighted');
            
            document.getElementById('orderAction').value = 'edit_order';
            document.getElementById('orderId').value = order.id;
            document.getElementById('orderNumber').value = order.order_number;
            document.getElementById('orderPhone').value = order.customer_phone;
            document.getElementById('orderStatus').value = order.status;
            document.getElementById('orderPriceEuro').value = centsToEuros(order.price);
            document.getElementById('orderArticles').value = order.articles || '';
            document.getElementById('orderSubscription').value = order.subscription_pkg || '';
            
            document.getElementById('orderForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        function resetOrderForm() {
            clearHighlights();
            document.getElementById('orderForm').reset();
            document.getElementById('orderAction').value = 'add_order';
            document.getElementById('orderId').value = '';
        }
        
        // Convert price before order form submission
        document.getElementById('orderForm').addEventListener('submit', function(e) {
            const euroValue = document.getElementById('orderPriceEuro').value;
            document.getElementById('orderPrice').value = eurosToCents(euroValue);
        });
    </script>
</body>
</html>
