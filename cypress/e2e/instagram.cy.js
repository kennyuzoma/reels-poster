describe('Instagram Login', () => {

    it('Login to instagram', () => {
        cy.visit('https://www.instagram.com/accounts/login/?source=desktop_nav')

        cy.wait(generateRandom(1000, 3000))
    })

    it('Click Login', () => {
        cy.get('body').contains('Log In').click();

        cy.wait(generateRandom(1000, 3000))
    })

    it('Enter Username and Password', () => {
        cy.get('[name="username"]').type('uzowave');

        cy.wait(generateRandom(2000, 5000))

        cy.get('[name="password"]').type('speedz1!');

        cy.wait(generateRandom(1000, 3000));
    })

    it('Submit and wait', () => {
        cy.get('button[type="submit"]').click();

        cy.wait(generateRandom(15000, 18000))
    })

    it('Land on main page and get cookies', () => {
        cy.get('body').contains('Go back to').click();

        cy.wait(generateRandom(5000,8000));


        cy.getCookies()
            .then((cookies) => {
                cy.writeFile('storage/igCookies.txt', cookies)
            })

        cy.end()
    })

})

function generateRandom(low, up) {
    const u = Math.max(low, up);
    const l = Math.min(low, up);
    const diff = u - l;
    const r = Math.floor(Math.random() * (diff + 1)); //'+1' because Math.random() returns 0..0.99, it does not include 'diff' value, so we do +1, so 'diff + 1' won't be included, but just 'diff' value will be.

    return l + r; //add the random number that was selected within distance between low and up to the lower limit.
}
