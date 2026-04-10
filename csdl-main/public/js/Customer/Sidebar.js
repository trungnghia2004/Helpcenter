var clickCount = 0;
var dropDowmTeacher = document.querySelector('#dropDowmTeacher')
document.querySelector(".show_logout")
    .addEventListener("click",function (){
        var popupElement =  document.querySelector('.drop_down');
        if (clickCount === 0) {
            popupElement.classList.remove("drop_downActive");
            clickCount++;
        }else{
            popupElement.classList.add("drop_downActive");

            clickCount = 0;
        }
    });


var clickSearchCount = 0;
document.querySelector(".search_icon")
    .addEventListener("click", function () {
        var searchElement = document.querySelector('.search_content')
        var searchBox = document.querySelector('.search-box')
        if (clickSearchCount === 0) {
            searchElement.classList.remove("outZoom");
            searchElement.classList.add("active");
            searchBox.classList.remove("outZoom");
            searchBox.classList.add("active");
            clickSearchCount++;
        }else{
            searchElement.classList.add("outZoom");
            searchBox.classList.add("outZoom");
            clickSearchCount = 0;
        }
    })
