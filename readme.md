# Что имеем

Модуль бонусной программы от Закусило.

# Что надо сделать

Правило корзины, которое начисляет разные бонусы в указанном размере

В корзине по правилу считается количество особых бонусов и добавляется к заказу в поле 'rule_bonuses'. Затем с этим полем пишется всё в базу.
В момент изменения заказа, модуль бонусной программы считает как обычно баллы, но ещё плюсует и особые баллы, взятые из поля заказа.

# Проблема

Не могу внятно связать правило корзины и объект заказа. При создании заказа сумма бонусов добавляется в заказ, но при смене статуса заказа - поле обнуляется и баллы не начисляются.