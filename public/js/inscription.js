
// on défini les variables

const slidePage = document.querySelector(".first-page");
const nextBtnFirst = document.querySelector(".firstNext");

const prevBtnSec = document.querySelector(".prev-1");
const nextBtnSec = document.querySelector(".next-1");
const prevBtnThird = document.querySelector(".prev-2");
const nextBtnThird = document.querySelector(".next-2");

const prevBtnFourth = document.querySelector(".prev-3");
const nextBtnFourth = document.querySelector(".next-3");

const prevBtnFifth = document.querySelector(".prev-4");


const submitBtn = document.querySelector(".submit");
var progress = document.querySelector('#progress');

var colWidth = document.querySelector('.step-col').clientWidth;
console.log(colWidth);


let current = 1;



// Les modifs quand on appui sur  next
nextBtnFirst.addEventListener("click",function(event){
      event.preventDefault();
    slidePage.style.marginLeft = "-25%";
    progress.style.width = (colWidth*2).toString()+ "px";
    console.log(progress.style.width);
    current+=1;
})
nextBtnSec.addEventListener("click",function(event){
      event.preventDefault();
    slidePage.style.marginLeft = "-50%";
    progress.style.width = (colWidth*3).toString()+ "px";
    console.log(progress.style.width);
    current+=1;
})
nextBtnThird.addEventListener("click",function(event){
      event.preventDefault();
    slidePage.style.marginLeft = "-75%";
    progress.style.width = (colWidth*4).toString()+ "px";
    console.log(progress.style.width);
    current+=1;
})
nextBtnFourth.addEventListener("click",function(event){
  event.preventDefault();
slidePage.style.marginLeft = "-100%";
progress.style.width = (colWidth*5).toString()+ "px";
console.log(progress.style.width);
current+=1;
})

// les modifs quand on appuie sur precedent

prevBtnSec.addEventListener("click",function(event){
      event.preventDefault();
    slidePage.style.marginLeft = "0%";
    progress.style.width = (progress.clientWidth - (colWidth+15)).toString()+ "px";
    console.log(progress.style.width);
    current-= 1;
    
});
prevBtnThird.addEventListener("click",function(event){
      event.preventDefault();
    slidePage.style.marginLeft = "-25%";
    progress.style.width = (progress.clientWidth - colWidth).toString()+ "px";
    console.log(progress.style.width);
    current-= 1;
    
});
prevBtnFourth.addEventListener("click",function(event){
      event.preventDefault();
    slidePage.style.marginLeft = "-50%";
    progress.style.width = (progress.clientWidth - colWidth).toString()+ "px";
    console.log(progress.style.width);
    current-= 1;
    
});
prevBtnFifth.addEventListener("click",function(event){
  event.preventDefault();
slidePage.style.marginLeft = "-75%";
progress.style.width = (progress.clientWidth - colWidth).toString()+ "px";
console.log(progress.style.width);
current-= 1;

});