function paintStars(starNumber) {
    for (let i = 1; i <= starNumber; i++) {
        document.querySelector('#star_' + i).style.fill = 'gold';
    }
    for (let i = starNumber + 1; i <= 5; i++) {
        document.querySelector('#star_' + i).style.fill = 'grey';
    }
}

function rateAdded() {
    if (this.status >= 400 && this.status <= 600) {
        let error = createErrorMessage(this.responseText);
        document.querySelector("#popup").appendChild(error);
    } else {
        document.querySelector('#popup').className = "";
        document.querySelector('#popup').style.display = 'none';
        window.location.reload();
    }
}

if (document.querySelector('#rateSellerButton') !== null) {
    document.querySelector('#rateSellerButton').addEventListener('click', function () {
        document.querySelector('#popup').style.display = "block";
        document.querySelector('#popup').className = "activeRateSeller";
        document.querySelector('.activeRateSeller img').addEventListener('click', function (event){
            document.querySelector('#popup').className = "";
            document.querySelector('#popup').style.display = 'none';
        })
        let stars = document.querySelectorAll('.in_stars svg');
        for (let star of stars) {
            star.addEventListener('mouseover', function (event) {
                paintStars(parseInt(star.id.charAt(star.id.length - 1)));
            })
            star.addEventListener('click', function (event) {
                const url = window.location.href;
                const user_id = parseInt(url.substring(url.lastIndexOf('/') + 1, url.length));
                sendAjaxRequest('post', '/api/users/addReview', {
                    rate: parseInt(star.id.charAt(star.id.length - 1)),
                    user_id: user_id
                }, rateAdded);
            })
        }
    })
}
