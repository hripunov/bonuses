{addcss file="%bonuses%/bonuses_mobile.css"}
<ion-grid id="cartBonusesWrapper" class="cartBonusesWrapper" margin-top *ngIf="authService.isAuth() && (cartdata.user_bonuses>0 || cartdata.total_bonuses>0)">
    <ion-row class="row">
        <ion-col class="cartitem bonusesCartItem col" no-padding no-margin col-12>
            <div class="bonusesDescription">
                <div text-left *ngIf="!cartdata.use_cart_bonuses && cartdata.user_bonuses>0"><b>Ваши бонусы - {literal}{{cartdata.user_bonuses}}{/literal}</b></div>
                <div text-left *ngIf="cartdata.total_bonuses>0"><b>Бонусы за заказ - {literal}{{cartdata.total_bonuses}}{/literal}</b></div>
            </div>
            <ion-item margin-top *ngIf="cartdata.user_bonuses>0">
              <ion-label text-wrap>{t}Перевести бонусы в скидку{/t}</ion-label>
              <ion-checkbox (ionChange)="changeUseBonuses()" [(ngModel)]="cartdata.use_cart_bonuses"></ion-checkbox>
            </ion-item>
        </ion-col>        
    </ion-row>
</ion-grid>