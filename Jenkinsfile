pipeline {
    agent any

    environment {
        // Mengambil file .env dari Jenkins Credentials
        DOT_ENV_FILE = credentials('dramabox-auth-env')
        APP_URL = "https://github.com/PTMajuJayaMakmur/drama-box-auth.git"
    }

    stage('Preparation') {
    steps {
        echo 'Cleaning Workspace and Fetching Code...'
        checkout scm
        
        script {
            // Pastikan tidak ada folder bernama .env yang tidak sengaja terbuat
            sh "rm -rf .env" 
            
            // Gunakan double quotes agar variabel terbaca, 
            // dan arahkan langsung ke nama file
            sh "cp ${DOT_ENV_FILE} .env"
        }
    }
}

        stage('Install Dependencies') {
            steps {
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'
                sh 'npm install && npm run build'
            }
        }

        stage('Security & Quality Check') {
            steps {
                echo 'Checking for vulnerabilities...'
                sh 'composer audit'
                echo 'Running Static Analysis (Pint)...'
                sh './vendor/bin/pint --test'
            }
        }

        stage('Database Migration (Testing)') {
            steps {
                // Menjalankan migrasi di env testing (pastikan DB testing siap)
                sh 'php artisan migrate --force'
            }
        }

        stage('Run Unit Tests') {
            steps {
                echo 'Running Auth Service Tests...'
                // Pastikan test suite untuk login/register berhasil 100%
                sh 'php artisan test --parallel'
            }
        }

        stage('Deploy to Staging') {
            when {
                branch 'main'
            }
            steps {
                echo 'Deploying drama-box-auth to Staging Environment...'
                // Contoh perintah deploy via SSH
                // sh 'ssh user@server "cd /var/www/auth && git pull origin main && php artisan migrate --force"'
            }
        }
    }

    post {
        success {
            echo 'Pipeline Drama Box Auth Berhasil!'
        }
        failure {
            echo 'Pipeline Gagal! Segera cek logs karena ini menyangkut layanan Auth.'
        }
    }
}
