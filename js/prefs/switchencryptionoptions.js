/**
 * Managing Keys for identities in the preferences UI.
 *
 * @author     Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

const switchEncryption = {
    // checks whether the smime checkbox is clicked and shows/hides
    checkSMIMEOption() {
      // get checkbox info
      const smimeSelect = document.getElementById('smimeselect');
      if (smimeSelect.checked) {
        const prefsSmimeContainer = document.querySelectorAll('.prefsSmimeContainer');
        prefsSmimeContainer.forEach((container) => {
          container.style.display = 'block';
        });
      } else {
        const prefsSmimeContainer = document.querySelectorAll('.prefsSmimeContainer');
        prefsSmimeContainer.forEach((container) => {
          container.style.display = 'none';
        });
        this.showKeys();
      }
    },
  
    // shows the keys of the identity
    showKeys() {
      // get the identityID
      const identity = document.getElementById('identity');
      const identityID = Number(identity.value);
  
      // doAction method from HordeCore
      HordeCore.doAction(
        'getIdentityPubKey',
        {
          identityID: identityID,
        },
        {
          callback: (response) => {
            // check for keys in the address book for the identity and show them
            const div = document.querySelectorAll('div.prefsSmimeContainer')[0];
  
            // ajax reloads things for three times for some reason, so make sure that nothing appears additionally. Remove extra appearances.
            if (div.previousElementSibling.id === 'addressbookpubkey') {
              div.previousElementSibling.remove();
              div.previousElementSibling.remove();
            }
  
            if (document.getElementById('smimeselect').checked) {
              const addressbookpubkey = document.getElementById('addressbookpubkey');
              if (addressbookpubkey) {
                addressbookpubkey.remove();
              }
              const addressbookonlyinfos = document.getElementById('addressbookonlyinfos');
              if (addressbookonlyinfos) {
                addressbookonlyinfos.remove();
              }
            }
  
            // if smime is not selected, some information on the keys in the address book shows
            if (!document.getElementById('smimeselect').checked) {
              const ul = document.createElement('ul');
              ul.setAttribute('id', 'addressbookpubkey');
  
              for (const key in response) {
                if (response.hasOwnProperty(key)) {
                  const li = document.createElement('li');
                  li.style.listStyleType = 'none';
                  const a = document.createElement('a');
                  a.setAttribute('href', response[key]);
                  a.textContent = key;
                  li.appendChild(a);
                  ul.appendChild(li);
                }
              }
  
              div.parentNode.insertBefore(ul, div);
  
              // create a text to inform the user
              const text = 'The following key from the address book is used for this identity if you want to use the address book only (without SMIME-keys):';
              const infodiv = document.createElement('div');
              infodiv.setAttribute('id', 'addressbookonlyinfos');
              infodiv.textContent = text;
              ul.parentNode.insertBefore(infodiv, ul);
            }
          },
        },
      );
    },
  };
  
  // on change of identity show chosen options for SMIME management or Addressbook keys management
  document.addEventListener('HordeIdentitySelect:change', switchEncryption.checkSMIMEOption.bind(switchEncryption));
  document.addEventListener('DOMContentLoaded', switchEncryption.checkSMIMEOption.bind(switchEncryption));
  const smimeselect = document.getElementById('smimeselect');
  if (smimeselect !== null) {
    smimeselect.addEventListener('click', switchEncryption.checkSMIMEOption.bind(switchEncryption));
  }
  