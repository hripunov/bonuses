<?php
namespace Bonuses\Model;

use Bonuses\Model\Orm\AddCommentBonusesToProduct;
use Bonuses\Model\Orm\BonusHistory;
use Catalog\Model\Orm\Product;
use Comments\Model\Orm\Comment;
use RS\Config\Loader;
use RS\Module\AbstractModel\BaseModel;
use RS\Orm\Request;
use Shop\Model\Orm\AbstractCartItem;
use Shop\Model\Orm\Order;
use Shop\Model\Orm\OrderItem;
use Users\Model\Orm\User;

class BonusCommentApi extends BaseModel
{
    protected $config;

    function __construct()
    {
        $this->config = Loader::byModule('bonuses');
    }

    /**
     * Проверяет начислены ли бонусы за комментарий
     *
     * @param Comment $comment - комментарий
     */
    function isCommentBonusesApplied(Comment $comment): ?int
    {
        return Request::make()
                    ->from(new AddCommentBonusesToProduct())
                    ->where([
                        'comment_id' => $comment['id'],
                    ])->exec()
                    ->getOneField('id', null);
    }

    /**
     * Проверяет начислены ли бонусы за комментарий к товару
     *
     * @param Product $product - товар
     * @param User $user - пользователь
     */
    function isAddedBonusesForProductComment(Product $product, User $user): ?int
    {
        return Request::make()
                    ->from(new AddCommentBonusesToProduct())
                    ->where([
                        'product_id' => $product['id'],
                        'user_id' => $user['id']
                    ])->exec()
                    ->getOneField('id', null);
    }

    /**
     * Проверяет есть в завершенных заказах товар
     *
     * @param Product $product - товар
     * @param User $user - товар
     */
    function isProductExistInSuccessOrderForUser(Product $product, User $user): bool
    {
        $rows = Request::make()
                    ->from(new OrderItem(), 'OI')
                    ->join(new Order(), 'OI.order_id=O.id', 'O')
                    ->where([
                        'OI.type' => AbstractCartItem::TYPE_PRODUCT,
                        'OI.entity_id' => $product['id'],
                        'O.status' => $this->config['bonuses_for_order_status'],
                        'O.user_id' => $user['id'],
                    ])
                    ->limit(1)
                    ->exec()
                    ->fetchSelected(null);

        return !empty($rows);
    }

}