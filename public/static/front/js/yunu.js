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

    if ($(window).width() > 991) {
        $('.dropdown').hover(function () {
            $(this).addClass('open');
        }, function () {
            $(this).removeClass('open');
        });
    } else {
        $('.dropdown').find('.arr').on('click', function () {
            $(this).parent().toggleClass('open');
        });
    };

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


/* ===== 易云CMS：查看更多（加载更多）分页 =====
 * 用法见前台标签调用手册「列表分页」章节：
 *   <div class="pages"><a href="javascript:;" class="yy-load-more"
 *        data-url="{:site_url('lists','more')}" data-cid="{$category.id}"
 *        data-page="2" data-size="5" data-target="#yy-list">查看更多</a></div>
 * data-offset=首屏已显示条数（模板自动输出 $more_offset），data-size=每次追加条数（代码中写），
 * data-target=列表容器选择器（模板中给列表 ul 加 id="yy-list"）。
 */
$(document).on('click', '.yy-load-more', function () {
        var btn = $(this);
        if (btn.hasClass('yy-more-loading') || btn.hasClass('yy-more-done')) return;
        var url    = btn.data('url') || '';
        var cid    = btn.data('cid') || 0;
        var offset = parseInt(btn.data('offset') || '0', 10);
        var size   = parseInt(btn.data('size') || '10', 10);
        var target = $(btn.data('target') || '#yy-list');
        if (!url || !target.length) return;

        btn.addClass('yy-more-loading');
        var old = btn.text();
        btn.text('加载中...');
        $.ajax({
            url: url,
            data: { cid: cid, offset: offset, size: size },
            dataType: 'html',
            timeout: 15000
        }).done(function (res) {
            var tmp = $('<div>').html(res);
            var box = tmp.find('#yy-list');
            if (!box.length || !$.trim(box.html())) {
                btn.addClass('yy-more-done').text('没有更多了').prop('disabled', true);
                return;
            }
            target.append(box.html());
            var got = box.children('li').length;
            btn.data('offset', offset + got);
            if (got < size) {
                btn.addClass('yy-more-done').text('没有更多了').prop('disabled', true);
            }
        }).fail(function () {
            alert('加载失败，请稍后重试');
        }).always(function () {
            btn.removeClass('yy-more-loading');
            if (!btn.hasClass('yy-more-done')) btn.text(old);
        });
    });
