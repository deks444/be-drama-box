pipeline {
    agent any

    options {
        // Menjaga kebersihan log dan membatasi jumlah build lama
        buildDiscarder(logRotator(numToKeepStr: '5'))
        // Mencegah dua build berjalan bersamaan
        disableConcurrentBuilds()
    }

    stages {
        stage('Checkout SCM') {
            steps {
                // Mengambil kode dari GitHub
                checkout scm
            }
        }

        stage('Setup Environment') {
            steps {
                echo 'Mengonfigurasi file .env...'
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // Menangani error "Not a directory" dan spasi pada path
                        sh 'rm -rf .env'
                        // Menggunakan cat untuk keamanan dan stabilitas path yang mengandung spasi
                        sh 'cat "${SECRET_ENV}" > .env'
                        sh 'chmod 600 .env'
                    }
                }
            }
        }

        stage('Install Composer') {
            steps {
                echo 'Memeriksa dan Menginstal Dependensi PHP...'
                script {
                    // Cek apakah composer sudah ada di sistem
                    def composerExists = sh(script: 'command -v composer', returnStatus: true) == 0
                    
                    if (composerExists) {
                        sh 'composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev'
                    } else {
                        echo 'Composer tidak ditemukan, mengunduh composer.phar secara lokal...'
                        sh 'curl -sS https://getcomposer.org/installer | php'
                        sh 'php composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev'
                    }
                }
            }
        }

        stage('Laravel Preparation') {
            steps {
                echo 'Menjalankan Laravel Artisan Tasks...'
                script {
                    // Pastikan PHP tersedia, jika menggunakan path khusus ganti 'php' menjadi path lengkap
                    sh 'php artisan key:generate --force'
                    sh 'php artisan storage:link'
                    
                    // Set Permission agar folder storage bisa ditulis oleh web server (misal: www-data)
                    sh 'chmod -R 775 storage bootstrap/cache'
                }
            }
        }

        stage('Database Migration') {
            steps {
                echo 'Menjalankan Migrasi Database...'
                // --force wajib digunakan untuk env produksi agar tidak minta konfirmasi interaktif
                sh 'php artisan migrate --force'
            }
        }

        stage('Optimization') {
            steps {
                echo 'Optimasi Cache Laravel...'
                sh 'php artisan config:cache'
                sh 'php artisan route:cache'
                sh 'php artisan view:cache'
            }
        }
    }

    post {
        success {
            echo '====================================='
            echo ' DEPLOYMENT DRAMA-BOX-AUTH BERHASIL! '
            echo '====================================='
        }
        failure {
            echo '====================================='
            echo '        DEPLOYMENT GAGAL!            '
            echo ' Periksa log di atas untuk detailnya.'
            echo '====================================='
        }
    }
}
