$(function () {
    new WOW({
        mobile: false
    }).init();



    $(window).scroll(function() {
        var scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
        if (scrollTop >= 95) {
            $('.header').addClass('header-fix');
        } else {
            $('.header').removeClass('header-fix');
        }
    });

    $('.search-btn a').on('click',function () {
        $('.search').stop().slideToggle(300);
     });
    

    $('.search').find('form').submit(function () {
        var val = $(this).find('input').val();
        if (!val) {
            alert('请输入关键字');
            return false
        }
    });

    $('.menu-btn').on('click', function () {
        $('.header').find('.nav').toggle();
    });

    // PC/移动端均支持 hover 显示二级导航；点击箭头亦可展开/收起
    $('.dropdown').hover(function () {
        $(this).addClass('open');
    }, function () {
        $(this).removeClass('open');
    });
    $('.dropdown').find('.arr').on('click', function () {
        $(this).parent().toggleClass('open');
    });

    $('.footer .icon a').hover(function () {
        $(this).find('.box').toggle();
    });

    $('.lanmu').find('.arr').on('click', function () {
        $(this).parent().toggleClass('open');
    });

    // 右侧滑动返回顶部
    $('.kf .kf-side').click(function(){
        //$('.kf').animate({ right: '-208' }, "slow");
        var rt = $('.kf').css("right");
        //alert(rt);
        var num = parseInt(rt);
        //alert(num);
        if(num < 0){
            $('.kf').animate({ right: '20px' }, "slow");
            $('.kf .kf-side span.arrow').addClass('on');
        }else{
            $('.kf').animate({ right: '-208px' }, "slow");
            $('.kf .kf-side span.arrow').removeClass('on');
        }
    });
    $('.kt-top span.close').click(function(){
        $('.kf').animate({ right: '-208px' }, "slow");
    });

    $('.kf .backTop').click(function() {
        $("html,body").stop().animate({ scrollTop: '0' }, 500);
    });
});

function tabsSwiper(menu, con, allowTouchMove) {
    var swiper = new Swiper(con, {
        speed: 500,
        spaceBetween: 10,
        autoHeight: true,
        allowTouchMove: !allowTouchMove,
        on: {
            slideChangeTransitionStart: function () {
                $(menu).find('li').eq(this.activeIndex).addClass('active').siblings().removeClass('active');
            }
        }
    });
    $(menu).on('click', 'li', function (e) {
        $(this).addClass('active').siblings().removeClass('active');
        swiper.slideTo($(this).index());
    });
}
