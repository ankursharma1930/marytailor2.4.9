define(['jquery'], function ($) {
  $(function () {
    $('#mySelect').change(function () {
      window.location.href = $('#mySelect').find(":selected").val();
    });
  });
  $("#askquebtn").click(function () {
    $("#question_form").slideDown("slow");
    $("#askquebtn").hide();
  });
  $("#closeform").click(function () {
    $("#question_form").hide();
    $("#askquebtn").show();
  });
  $('.action.submit.primary').click(function () {
    $("#question_form").show();
  });
  $(".question-section").click(function () {
    $(this).next().slideToggle();
    if ($(".question-section").hasClass("active")) {
      $(".question-section").removeClass("active");
    } else {
      $(this).addClass("active");
    }
  });
  if (window.location.href.indexOf("#productfaq") > -1) {
    var mainInterval;
    mainInterval = setInterval(function () {
      if ($('#tab-label-productfaq').length) {
        $('#productfaq').show();
        clearInterval(mainInterval);
      }
    }, 500);
  }
  if ($('.pager .pages .items.pages-items')) {
    $('.pager .pages .items.pages-items').children().each(function(index){
      if (index <= 1) {
        var href = $(this).find("a").attr('href');
        $(this).find("a").attr('href', href + '#productfaq');
      }
    });
  }
  if (document.location.search.length) {
    var interval;
    interval = setInterval(function () {
      if ($('#tab-label-productfaq').length) {
        $('#productfaq').show();
        clearInterval(interval);
      }
    }, 500);
    if (window.location.href.indexOf("#productfaq") > -1) {
      var url = document.location.href;
      document.location = url;
    } else {
      var url = document.location.href + "#productfaq";
      document.location = url;
    }
  }
});