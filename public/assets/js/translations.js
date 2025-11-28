// Translation helper for JavaScript
// This file provides client-side translations that match the server-side Symfony translations

const translations = {
    fr: {
        'courses.view': 'Voir',
        'courses.edit': 'Modifier',
        'courses.delete': 'Supprimer',
        'courses.info.runners': 'coureur(s)',
        'courses.info.no_parcours': 'Aucun parcours',
        'courses.status.active': 'Active',
        'courses.no_courses': 'Aucune course disponible.',
        'courses.create_course': 'Créer une course',
        'courses.error_loading': 'Erreur de chargement des courses',
        'courses.messages.created': 'Course créée avec succès !',
        'courses.messages.deleted': 'Course supprimée avec succès !',
        'courses.messages.confirm_delete': 'Êtes-vous sûr de vouloir supprimer la course "%name%" ?',
        'courses.messages.error_create': 'Erreur lors de la création',
        'courses.messages.error_delete': 'Erreur lors de la suppression',
        'courses.modal.create_title': 'Créer une Nouvelle Course',
        'courses.modal.name_label': 'Nom de la course',
        'courses.modal.parcours_label': 'Parcours associé',
        'courses.modal.parcours_select': 'Sélectionner un parcours...',
        'courses.modal.nb_runners_label': 'Nombre de coureurs',
        'courses.modal.cancel': 'Annuler',
        'courses.modal.create': 'Créer',
        
        'parcours.view': 'Voir',
        'parcours.edit': 'Modifier',
        'parcours.delete': 'Supprimer',
        'parcours.qr_codes': 'Qr Codes',
        'parcours.info.beacons': 'balise(s)',
        'parcours.status.in_creation': 'En cours de création',
        'parcours.no_parcours': 'Aucun parcours disponible.',
        'parcours.create_parcours': 'Créer un parcours',
        'parcours.error_loading': 'Erreur de chargement des parcours',
        'parcours.messages.updated': 'Modifications enregistrées avec succès !',
        'parcours.messages.deleted': 'Parcours supprimé avec succès !',
        'parcours.messages.confirm_delete': 'Êtes-vous sûr de vouloir supprimer le parcours "%name%" ?',
        'parcours.messages.error_update': 'Erreur lors de la sauvegarde',
        'parcours.messages.error_delete': 'Erreur lors de la suppression'
    },
    en: {
        'courses.view': 'View',
        'courses.edit': 'Edit',
        'courses.delete': 'Delete',
        'courses.info.runners': 'runner(s)',
        'courses.info.no_parcours': 'No course',
        'courses.status.active': 'Active',
        'courses.no_courses': 'No courses available.',
        'courses.create_course': 'Create a course',
        'courses.error_loading': 'Error loading courses',
        'courses.messages.created': 'Course created successfully!',
        'courses.messages.deleted': 'Course deleted successfully!',
        'courses.messages.confirm_delete': 'Are you sure you want to delete the course "%name%"?',
        'courses.messages.error_create': 'Error creating course',
        'courses.messages.error_delete': 'Error deleting course',
        'courses.modal.create_title': 'Create a New Course',
        'courses.modal.name_label': 'Course name',
        'courses.modal.parcours_label': 'Associated course',
        'courses.modal.parcours_select': 'Select a course...',
        'courses.modal.nb_runners_label': 'Number of runners',
        'courses.modal.cancel': 'Cancel',
        'courses.modal.create': 'Create',
        
        'parcours.view': 'View',
        'parcours.edit': 'Edit',
        'parcours.delete': 'Delete',
        'parcours.qr_codes': 'QR Codes',
        'parcours.info.beacons': 'beacon(s)',
        'parcours.status.in_creation': 'In progress',
        'parcours.no_parcours': 'No courses available.',
        'parcours.create_parcours': 'Create a course',
        'parcours.error_loading': 'Error loading courses',
        'parcours.messages.updated': 'Changes saved successfully!',
        'parcours.messages.deleted': 'Course deleted successfully!',
        'parcours.messages.confirm_delete': 'Are you sure you want to delete the course "%name%"?',
        'parcours.messages.error_update': 'Error saving',
        'parcours.messages.error_delete': 'Error deleting'
    },
    eu: {
        'courses.view': 'Ikusi',
        'courses.edit': 'Aldatu',
        'courses.delete': 'Ezabatu',
        'courses.info.runners': 'korrikalari',
        'courses.info.no_parcours': 'Ibilbiderik ez',
        'courses.status.active': 'Aktiboa',
        'courses.no_courses': 'Ez dago lasterketarik eskuragarri.',
        'courses.create_course': 'Sortu lasterketa bat',
        'courses.error_loading': 'Errorea lasterketak kargatzean',
        'courses.messages.created': 'Lasterketa ondo sortu da!',
        'courses.messages.deleted': 'Lasterketa ondo ezabatu da!',
        'courses.messages.confirm_delete': '"%name%" lasterketa ezabatu nahi duzu?',
        'courses.messages.error_create': 'Errorea sortzerakoan',
        'courses.messages.error_delete': 'Errorea ezabatzerakoan',
        'courses.modal.create_title': 'Sortu Lasterketa Berria',
        'courses.modal.name_label': 'Lasterketa izena',
        'courses.modal.parcours_label': 'Lotutako ibilbidea',
        'courses.modal.parcours_select': 'Hautatu ibilbide bat...',
        'courses.modal.nb_runners_label': 'Korrikalarien kopurua',
        'courses.modal.cancel': 'Utzi',
        'courses.modal.create': 'Sortu',
        
        'parcours.view': 'Ikusi',
        'parcours.edit': 'Aldatu',
        'parcours.delete': 'Ezabatu',
        'parcours.qr_codes': 'QR Kodeak',
        'parcours.info.beacons': 'baliza',
        'parcours.status.in_creation': 'Sortzen',
        'parcours.no_parcours': 'Ez dago ibilbiderik eskuragarri.',
        'parcours.create_parcours': 'Sortu ibilbide bat',
        'parcours.error_loading': 'Errorea ibilbideak kargatzean',
        'parcours.messages.updated': 'Aldaketak ondo gorde dira!',
        'parcours.messages.deleted': 'Ibilbidea ondo ezabatu da!',
        'parcours.messages.confirm_delete': '"%name%" ibilbidea ezabatu nahi duzu?',
        'parcours.messages.error_update': 'Errorea gordetzerakoan',
        'parcours.messages.error_delete': 'Errorea ezabatzerakoan'
    }
};

function trans(key, params = {}) {
    const locale = document.documentElement.lang || localStorage.getItem('locale') || 'fr';
    let translation = translations[locale]?.[key] || translations['fr']?.[key] || key;
    
    // Replace parameters
    for (const [param, value] of Object.entries(params)) {
        translation = translation.replace(`%${param}%`, value);
    }
    
    return translation;
}

// Export for use in other scripts
window.trans = trans;
