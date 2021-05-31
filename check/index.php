<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/header.css">
    <link rel="stylesheet" href="./css/filter.css">
    <link rel="stylesheet" href="./css/main.css">
    <link rel="stylesheet" href="./css/footer.css">
    <title>AutoRazborka</title>
</head>
<body>
    
    <?php require_once './src/header.php' ?>
    <?php require_once './src/items.php' ?>

    <main>
        <div class="content content-grid">
            <!-- Left -->
            <div class="grid-left">

                <!-- Breadcrumbs -->
                <div class="bread">
                    <a href="/">Главная</a>
                    <span><svg><use xlink:href="./img/sprite.svg#arr-right"></use></svg></span>
                    <a href="/">Машинокомплект</a>
                </div>

                <!-- Head page -->
                <div class="flex flex-aife flex-jcsb">
                    <!-- Title -->
                    <h1 class="title">Машинокомплект</h1>

                    <!-- Change -->
                    <div class="change flex flex-aic">
                        <select name="sort" id="sort" class="form-input">
                            <option value="new">Сначала новые</option>
                            <option value="old">Сначала старые</option>
                            <option value="low_price">Сначала дешевые</option>
                            <option value="big_price">Сначала дорогие</option>
                        </select>

                        <div class="change-view flex flex-aic">
                            <input type="radio" name="view" id="list" value="list" checked class="change-view-input">
                            <label for="list" class="change-view-label">
                                <svg><use xlink:href="./img/sprite.svg#menu"></use></svg>
                            </label>
                            <input type="radio" name="view" id="grid" value="grid" class="change-view-input">
                            <label for="grid" class="change-view-label">
                                <svg><use xlink:href="./img/sprite.svg#grid"></use></svg>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Body page -->
                <div class="items">

                    <?php foreach($items as $item) : ?>
                    <article class="item">
                        <a href="#" class="item-img" style="background-image: url(<?=$item['img'];?>);">&nbsp;</a>
                        <div class="item-text">
                            <!-- Item head -->
                            <div class="item-text-head flex flex-aifs flex-jcsb">
                                <div class="item-text-head-title">
                                    <a href="#" class="item-title"><?=$item['title'];?></a>
                                    <small>
                                        <span><svg><use xlink:href="./img/sprite.svg#time"></use></svg> Сегодня в 21:30</span>
                                        <span><svg><use xlink:href="./img/sprite.svg#location"></use></svg> Москва</span>
                                        <a href="#">
                                            <svg><use xlink:href="./img/sprite.svg#company"></use></svg> ЮнитАвто
                                        </a>
                                    </small>
                                </div>
                                <div class="item-text-head-price">
                                    <b class="item-price"><?=$item['price'];?> ₽</b>
                                    <small>15 000</small>
                                </div>
                            </div>
                            
                            <!-- Item data -->
                            <div class="item-text-data flex flex-aifs flex-jcsb">
                                <div class="item-text-data-cell">
                                    <small>Объем:</small>
                                    <b>18 литров</b>
                                </div>
                                <div class="item-text-data-cell">
                                    <small>Топливо:</small>
                                    <b>Гибрид (бензин + газ)</b>
                                </div>
                                <div class="item-text-data-cell">
                                    <small>Кузов:</small>
                                    <b>Седан</b>
                                </div>
                                <div class="item-text-data-cell">
                                    <small>Коробка:</small>
                                    <b>Механика</b>
                                </div>
                            </div>
                            
                            <!-- Item data -->
                            <div class="item-text-description">
                                <?=$item['description'];?>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>

                </div>

            </div>
            
            <!-- Right -->
            <div class="grid-right">
                <div class="filter">

                    <div class="filter-item filter-history">
                        <small>Ранее вы искали:</small>
                        <div class="filter-item-body flex flex-wrap">
                            <a href="#" class="filter-history-tag">BMW / X5 / 2018</a>
                            <a href="#" class="filter-history-tag">Mercedes / Benz / 2015</a>
                        </div>
                    </div>
                    
                    <div class="filter-item filter-basic">
                        <small>Основные параметры:</small>
                        <div class="filter-item-body">
                            <select name="marka" class="form-input full">
                                <option value="">Марка автомобиля</option>
                                <option value="audi">Audi</option>
                                <option value="bmw">BMW</option>
                            </select>
                            
                            <select name="model" class="form-input full">
                                <option value="">Модель автомобиля</option>
                                <option value="x5">x5</option>
                                <option value="x6">x6</option>
                            </select>

                            <div class="filter-select">
                                <div class="filter-select-head">
                                    <span>Год выпуска</span>
                                    <svg><use xlink:href="./img/sprite.svg#arr-down"></use></svg>
                                </div>
                                <div class="filter-select-body">
                                    <div class="filter-check">
                                        <input type="checkbox" name="checkme" id="checkme" class="filter-check-input">
                                        <label for="checkme" class="filter-check-label">
                                            <span>
                                                <svg><use xlink:href="./img/sprite.svg#check"></use></svg>
                                            </span>
                                            2020
                                        </label>
                                    </div>
                                    <div class="filter-check">
                                        <input type="checkbox" name="checkme2" id="checkme2" class="filter-check-input">
                                        <label for="checkme2" class="filter-check-label">
                                            <span>
                                                <svg><use xlink:href="./img/sprite.svg#check"></use></svg>
                                            </span>
                                            2019
                                        </label>
                                    </div>
                                    <div class="filter-check">
                                        <input type="checkbox" name="checkme3" id="checkme3" class="filter-check-input">
                                        <label for="checkme3" class="filter-check-label">
                                            <span>
                                                <svg><use xlink:href="./img/sprite.svg#check"></use></svg>
                                            </span>
                                            2018
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="filter-select">
                                <div class="filter-select-head">
                                    <span>Страна и город</span>
                                    <svg><use xlink:href="./img/sprite.svg#arr-down"></use></svg>
                                </div>
                                <div class="filter-select-body">
                                    <div class="filter-check">
                                        <input type="checkbox" name="checkme" id="checkme11" class="filter-check-input">
                                        <label for="checkme11" class="filter-check-label">
                                            <span>
                                                <svg><use xlink:href="./img/sprite.svg#check"></use></svg>
                                            </span>
                                            Москва
                                        </label>
                                    </div>
                                    <div class="filter-check">
                                        <input type="checkbox" name="checkme2" id="checkme22" class="filter-check-input">
                                        <label for="checkme22" class="filter-check-label">
                                            <span>
                                                <svg><use xlink:href="./img/sprite.svg#check"></use></svg>
                                            </span>
                                            Санкт-питербург
                                        </label>
                                    </div>
                                    <div class="filter-check">
                                        <input type="checkbox" name="checkme3" id="checkme33" class="filter-check-input">
                                        <label for="checkme33" class="filter-check-label">
                                            <span>
                                                <svg><use xlink:href="./img/sprite.svg#check"></use></svg>
                                            </span>
                                            Казань
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="filter-item filter-extra">
                        <small>Дополнительные параметры:</small>
                        <div class="filter-item-body">
                            <select name="marka" class="form-input full">
                                <option value="">Поколение автомобиля</option>
                                <option value="audi">Audi</option>
                                <option value="bmw">BMW</option>
                            </select>
                            
                            <select name="model" class="form-input full">
                                <option value="">Объем двигателя</option>
                                <option value="x5">x5</option>
                                <option value="x6">x6</option>
                            </select>

                            <select name="model" class="form-input full">
                                <option value="">Тип топлива</option>
                                <option value="x5">x5</option>
                                <option value="x6">x6</option>
                            </select>
                            
                            <select name="model" class="form-input full">
                                <option value="">Тип коробки передач</option>
                                <option value="x5">x5</option>
                                <option value="x6">x6</option>
                            </select>

                            <select name="model" class="form-input full">
                                <option value="">Тип кузова</option>
                                <option value="x5">x5</option>
                                <option value="x6">x6</option>
                            </select>
                        </div>
                    </div>

                    <button class="btn btn-full btn-green">Применить</button>
                    <button class="btn btn-full btn-red">Очистить фильтр</button>
                </div>
            </div>
        </div>
    </main>

    <?php require_once './src/footer.php' ?>

    <script
        src="https://code.jquery.com/jquery-3.4.1.min.js"
        integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
        crossorigin="anonymous">
    </script>
    <script src="./js/main.js"></script>
</body>
</html>