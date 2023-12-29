/**
 * Класс работы с бонусами
 */
class CartBonuses{
    cartBonusesWrapper = '#cartBonusesWrapper';
    offerInput = "[name='offer']";
    amountInput = "[name='amount']";
    concomitantsInput = "[name*='concomitant[']";
    multiOffersInput = '[name^="multioffers["]';
    applyBonusesInput = '[name="use_cart_bonuses"]';
    applyBonusesInputStyled = '#use_cart_bonuses-styler';
    productBonusesLabel = '#productBonuses';
    formStyledCheckbox = '.jq-checkbox';


    /**
     * Создаёт обработку для события изменения корзины
     * @param {Event} event - событие
     * @param {HTMLElement} bonusesBlockWrapper - обертка для блока применения бонусов
     * @returns {boolean}
     * @private
     */
    handleApplyBonusesInCart(event, bonusesBlockWrapper)
    {
        const inputChange = event.target;
        const form = inputChange.closest('form');
        if (form){
            if (!bonusesBlockWrapper.querySelector(this.applyBonusesInput).checked){
                form.insertAdjacentHTML('beforeend', '<input type="hidden" name="use_cart_bonuses" value="0"/>');
            }
            const action = form.action;

            let changeEvent;
            if (action.indexOf('/block-cart/') !== -1) { // Если это блок корзины в оформлении заказа
                changeEvent = new Event('change', { bubbles: true });
                form.querySelector('input[name*="][amount]"]').dispatchEvent(changeEvent);
            }else{ // Если это страница отдельная корзины
                const eventType = form.querySelector('.rs-amount') ? 'change' : 'keyup';
                changeEvent = new Event(eventType, { bubbles: true });
                form.querySelector('input[name*="][amount]"]').dispatchEvent(changeEvent);
            }
        }else{ // Если формы нет, попробуем изменить через данные через блок и обновить страницу
            const input = inputChange.closest('[data-action]');
            if (input){
                const action = input.dataset.action;
                this._triggerCheckoutApplyBonuses(action, input);
            }
        }

        return false;
    }

    /**
     * Переключает в оформлении заказа примененние бонусов, если формы корзины там нет
     * @param {String} action - url для запроса
     * @param {HTMLInputElement} input - значение для переключения
     * @private
     */
    _triggerCheckoutApplyBonuses(action, input)
    {
        input.disabled = true;
        const formData = new FormData();
        formData.append('use_cart_bonuses', input.checked ? 1 : 0);
        fetch(action, {
            method: 'POST',
            body: formData
        }).then(res => res.json())
          .then(res => {
              input.disabled = false;
              if (input.classList.contains('updateConfirmPage') && RsJsCore?.components?.checkout){
                  RsJsCore.components.checkout.updateBlocks();
                  document.querySelectorAll('.appliedBonusesNow').forEach((appliedBonusesNow) => {
                      appliedBonusesNow.innerHTML = res.appliedBonuses;
                  });
              }
          });
    }

    /**
     * Блок применения бонусов в корзине. Списывание бонусов с переводом их в скидку
     */
    bonusesApplyInCart()
    {
        document.addEventListener('change', (event) => {
            const target = event.target;
            if (target.closest(this.applyBonusesInput)){
                const bonusesBlockWrapper = document.querySelector(this.cartBonusesWrapper);
                this.handleApplyBonusesInCart(event, bonusesBlockWrapper);
            }
        });

        setTimeout(() => {
            if (window.jQuery && $(this.applyBonusesInputStyled).length){
                document.querySelector(this.applyBonusesInputStyled).addEventListener('click', (event) => {
                    const changeEvent = new Event('change', { bubbles: true });
                    document.querySelector(this.applyBonusesInput).dispatchEvent(changeEvent);
                });
            }
        }, 500);
    }

    /**
     * Показывает сколько бонусов отображать для товара
     */
    changeBonusesOnProduct()
    {
        let bonuses = 0;
        let offer = document.querySelector(this.offerInput);
        if (offer){ //Если есть комплектация
            let val = offer.value;
            if (offer.tagName === "INPUT" && offer.type.toLowerCase() === "radio"){
                val = document.querySelector(this.offerInput + ":checked").value * 1;
            }
            if (window.global.bonuses.offer_bonuses) {
                bonuses = window.global.bonuses.offer_bonuses[val] * 1;
            }
        }else if(window.global.bonuses.offer_bonuses){
            bonuses = window.global.bonuses.offer_bonuses[0] * 1;
        }

        //Если есть поле для ввода количества
        let amountInput = document.querySelector(this.amountInput);
        if (amountInput){
            bonuses = bonuses * (amountInput.value * 1);
        }

        //Смотрим, есть ли сопутствующие и считаем их бонусы
        if (window.global.bonuses.product_concomitans){
            const concomitants = document.querySelectorAll(this.concomitantsInput);
            if (concomitants.length){
                concomitants.forEach((concomitantInput) => {
                    if (concomitantInput.checked){
                        bonuses += parseInt(window.global.bonuses.product_concomitans[concomitantInput.value], 10);
                    }
                });
            }
        }

        //Выведем бонусы
        const bonusesLabel = document.querySelector(this.productBonusesLabel);
        if (bonusesLabel){
            bonusesLabel.innerHTML = bonuses
        }
    }

    /**
     * Инициализирует работу
     */
    init() {
        this.bonusesApplyInCart();

        //Если сведения по бонусам присутствуют
        if (window.global && window.global.bonuses){
            if (window.global.bonuses.offer_bonuses){ //Для комплектаций
                /**
                 * Смена комплектации и показ сколько бонусов для комплетации показать
                 */
                document.addEventListener('change', (event) => {
                    const target = event.target;
                    if (target.closest(this.offerInput)){
                        this.changeBonusesOnProduct();
                    }
                    if (target.closest(this.multiOffersInput)){
                        this.changeBonusesOnProduct();
                    }
                });
            }
            //Смена количества
            document.addEventListener('change', (event) => {
                const target = event.target;
                if (target.closest(this.amountInput)){
                    setTimeout(() => this.changeBonusesOnProduct(), 500);
                }
                if (target.closest('.rs-inc')){
                    setTimeout(() => this.changeBonusesOnProduct(), 500);
                }
                if (target.closest('.rs-dec')){
                    setTimeout(() => this.changeBonusesOnProduct(), 500);
                }
            });

            if (window.global.bonuses.product_concomitans){ //Если есть сопуствующие
                document.addEventListener('change', (event) => {
                    const target = event.target;
                    if (target.closest(this.concomitantsInput)){
                        this.changeBonusesOnProduct();
                    }
                });
                document.addEventListener('DOMContentLoaded', () => {
                    setTimeout(() => {
                        document.querySelectorAll(this.formStyledCheckbox).forEach((concomItem) => {
                            concomItem.addEventListener('click', (event) => {
                                const styledCheckbox = event.target;
                                if (styledCheckbox.closest(this.formStyledCheckbox)){
                                    if (styledCheckbox.closest('.concomitantItem')){
                                        this.changeBonusesOnProduct();
                                    }
                                }
                            });
                        });
                    }, 500);
                });
            }
        }
    }
}

const cartBonusesClass = new CartBonuses();
cartBonusesClass.init();