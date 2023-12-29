(function()
{
    //Добавим параметры секции custom
    if (!this.config){
        this.config = {};
    }
    if (!this.config['custom']){
        this.config['custom'] = {};
    }
    //Добавим флаг, что мы используем бонусы
    this.config['custom']['use_cart_bonuses'] = (this.cartdata.use_cart_bonuses) ? 1 : 0;
    
    //Добавляем содержимое запроса
    this.httpRequestService.addRequestToBuffer(this.cartService.url, this.config, this);
    //Отправляем запрос на сервер.
    this.httpRequestService.sendRequestsPartially();
})