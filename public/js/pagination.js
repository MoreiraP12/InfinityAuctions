function pagination() {
    let lis = document.querySelectorAll(".pagination ul a");
    for (let li of lis) {
        li.addEventListener("click", function () {
            notification.className = "is-active";
        });
    } 
}

function seeNext() {
    let buttons = document.querySelectorAll(".pagination ul > li");

    //onclick

    buttons[1].addEventListener("click", function () {
        console.log("BATATA");
        let lis = document.querySelectorAll(".pagination ul a");
        //ler em que página está
        let pageNumber = document.getElementById("pageNumberUsers").innerHTML;
        console.log(pageNumber);

        //tornar visivel as próximas 5 visíveis
        for(i =pageNumber; i < pageNumber +5; i++){
            lis[i].style.display = "block";
        }
        
        //tornar invisivel as passadas 5
        for(i =pageNumber-5; i < pageNumber; i++){
            lis[i].style.display = "none";
        }
    });


    
}

function seePrevious() {
    let buttons = document.querySelectorAll(".pagination ul > li");

    //onclick

    buttons[1].addEventListener("click", function () {
        let lis = document.querySelectorAll(".pagination ul a");
        //ler em que página está
        let pageNumber = document.getElementById("pageNumberUsers").innerHTML;
        console.log(pageNumber);

        //tornar visivel as próximas 5 visíveis
        for(i =pageNumber; i < pageNumber +5; i++){
            lis[i].style.display = "none";
        }
        
        //tornar invisivel as passadas 5
        for(i =pageNumber-5; i < pageNumber; i++){
            lis[i].style.display = "block";
        }
    });


}

seeNext();
seePrevious();
pagination();