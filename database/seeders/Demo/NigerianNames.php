<?php

namespace Database\Seeders\Demo;

/**
 * Name pools for the demo school, grouped by ethnic group so a family's
 * surname, children's given names and religion fit together the way they
 * do in real Nigerian schools.
 */
class NigerianNames
{
    /** weight = how common the group is in a Lagos private school */
    public static function groups(): array
    {
        return [
            'yoruba' => [
                'weight' => 34, 'muslim' => 0.30,
                'male' => ['Adebayo', 'Oluwaseun', 'Babatunde', 'Ayodele', 'Temitope', 'Olamide', 'Ifeoluwa', 'Damilare', 'Oluwadamilare', 'Tobiloba', 'Ayomide', 'Kolawole', 'Oluwafemi', 'Adewale', 'Segun', 'Tunde', 'Kehinde', 'Taiwo', 'Abiodun', 'Olumide', 'Moyinoluwa', 'Ireoluwa', 'Anuoluwapo', 'Opeyemi', 'Rotimi', 'Ademola', 'Adeolu', 'Folarin', 'Oluwatobi', 'Mayowa'],
                'female' => ['Oluwatobiloba', 'Adaeze', 'Temilade', 'Ifeoluwa', 'Boluwatife', 'Morenike', 'Oluwabukola', 'Titilayo', 'Folasade', 'Omolara', 'Yetunde', 'Ayomide', 'Damilola', 'Oluwadamilola', 'Mojisola', 'Abimbola', 'Eniola', 'Toluwalase', 'Feyisara', 'Adunni', 'Iyanuoluwa', 'Fadekemi', 'Olamide', 'Anuoluwa', 'Omotola', 'Aduke', 'Modupe', 'Bolanle', 'Temitayo', 'Similoluwa'],
                'surnames' => ['Adeyemi', 'Ogunleye', 'Balogun', 'Adebisi', 'Oyelaran', 'Afolabi', 'Olawale', 'Ajayi', 'Fashola', 'Ogundipe', 'Adesanya', 'Akinola', 'Bakare', 'Salami', 'Oladipo', 'Olatunji', 'Adewumi', 'Ogunbanjo', 'Omotosho', 'Lawal', 'Alabi', 'Oyebanji', 'Fagbemi', 'Ayoola', 'Akintola', 'Odunsi', 'Sanusi', 'Adegoke', 'Olaniyan', 'Thompson-Bello'],
            ],
            'igbo' => [
                'weight' => 26, 'muslim' => 0.0,
                'male' => ['Chinedu', 'Chukwuemeka', 'Ikenna', 'Obinna', 'Chidera', 'Somtochukwu', 'Kelechi', 'Uchenna', 'Nnamdi', 'Chibuike', 'Ugochukwu', 'Emeka', 'Tobenna', 'Munachimso', 'Ifeanyi', 'Chimaobi', 'Kosisochukwu', 'Onyedikachi', 'Arinze', 'Chizoba', 'Nwabueze', 'Zubby', 'Ebuka', 'Kenechukwu', 'Ikechukwu'],
                'female' => ['Chiamaka', 'Adaeze', 'Ngozi', 'Nkechi', 'Ifunanya', 'Chidinma', 'Amarachi', 'Uchechi', 'Chinyere', 'Oluchi', 'Somkenechukwu', 'Ebube', 'Obiageli', 'Ifeoma', 'Nneka', 'Kamsiyochukwu', 'Ugonna', 'Ogechi', 'Chioma', 'Nnenna', 'Adanna', 'Ezinne', 'Munachi', 'Tochi', 'Ijeoma'],
                'surnames' => ['Okafor', 'Nwosu', 'Eze', 'Okonkwo', 'Onyeama', 'Chukwu', 'Nnadi', 'Obi', 'Anyanwu', 'Umeh', 'Ugwu', 'Okeke', 'Nwankwo', 'Ibe', 'Mbah', 'Onwuka', 'Ezeani', 'Okoye', 'Agu', 'Ibekwe', 'Nwachukwu', 'Ejiofor', 'Oparah', 'Igwe', 'Ndukwe', 'Uzoma', 'Anozie', 'Obiora'],
            ],
            'hausa' => [
                'weight' => 14, 'muslim' => 1.0,
                'male' => ['Abdullahi', 'Ibrahim', 'Usman', 'Musa', 'Aliyu', 'Yusuf', 'Sani', 'Bashir', 'Garba', 'Suleiman', 'Mohammed', 'Aminu', 'Nasiru', 'Umar', 'Sadiq', 'Hamza', 'Idris', 'Shehu', 'Zayyad', 'Auwal'],
                'female' => ['Aisha', 'Fatima', 'Zainab', 'Hauwa', 'Maryam', 'Hadiza', 'Khadija', 'Ummi', 'Rukayya', 'Amina', 'Safiya', 'Halima', 'Nafisa', 'Jamila', 'Zulaihat', 'Bilkisu', 'Asma\'u', 'Sa\'adatu'],
                'surnames' => ['Abubakar', 'Danjuma', 'Bello', 'Yakubu', 'Mohammed', 'Tijani', 'Garba', 'Lawal', 'Dikko', 'Ahmad', 'Sambo', 'Maikano', 'Jibrin', 'Baba', 'Gambo', 'Shuaibu', 'Dantata', 'Waziri', 'Isa', 'Kabir'],
            ],
            'edo_delta' => [
                'weight' => 8, 'muslim' => 0.03,
                'male' => ['Osaze', 'Osagie', 'Efosa', 'Nosakhare', 'Eghosa', 'Ose', 'Ovie', 'Tega', 'Oghenekaro', 'Ejiro', 'Avwerosuoghene', 'Oghenemaro', 'Erhunmwunse', 'Ikponmwosa', 'Obaro'],
                'female' => ['Osarugue', 'Ehi', 'Omosede', 'Efe', 'Ohunene', 'Oghenetega', 'Esosa', 'Aisosa', 'Ekaette', 'Oghenekevwe', 'Ufuoma', 'Erhuvwu', 'Idia'],
                'surnames' => ['Igbinedion', 'Osagie', 'Aigbe', 'Ehigiator', 'Omoruyi', 'Uwaifo', 'Ighodaro', 'Oghenekaro', 'Ovie', 'Odiase', 'Edokpayi', 'Oboh', 'Ekhator', 'Erhabor', 'Okoh'],
            ],
            'south_south' => [
                'weight' => 8, 'muslim' => 0.0,
                'male' => ['Ekpenyong', 'Idara', 'Utibe', 'Edidiong', 'Ubong', 'Imoh', 'Okon', 'Mfon', 'Inyang', 'Etim', 'Ebiye', 'Preye', 'Timi', 'Ebizimor', 'Tamunoiyala', 'Bamidele'],
                'female' => ['Ime', 'Idorenyin', 'Mfonobong', 'Aniekan', 'Nsikak', 'Affiong', 'Ekemini', 'Ebiere', 'Tonye', 'Ibiere', 'Ofonime', 'Ukeme', 'Iniobong'],
                'surnames' => ['Akpan', 'Udoh', 'Essien', 'Bassey', 'Etukudo', 'Ekong', 'Inyang', 'Umoh', 'Okon', 'Asuquo', 'Ibanga', 'Diri', 'Alagoa', 'Ebikeme', 'Preye-Tamuno', 'Ogbonna-Brown'],
            ],
            'middle_belt' => [
                'weight' => 6, 'muslim' => 0.15,
                'male' => ['Terkimbi', 'Aondoakaa', 'Tersoo', 'Msughter', 'Terna', 'Ochai', 'Ojonugwa', 'Agbo', 'Danladi', 'Gideon', 'Dauda', 'Yakubu', 'Joshua', 'Istifanus', 'Bitrus', 'Pam', 'Dung', 'Gyang', 'Daniel'],
                'female' => ['Mbalumun', 'Mimidoo', 'Aondona', 'Ene', 'Ojoma', 'Ochanya', 'Ladi', 'Rahila', 'Hannatu', 'Naomi', 'Lami', 'Rifkatu', 'Salome', 'Ngo', 'Chundung'],
                'surnames' => ['Iorkaa', 'Orshi', 'Adeka', 'Ugbede', 'Ocheni', 'Ameh', 'Idoko', 'Attah', 'Audu', 'Yohanna', 'Gyang', 'Dung', 'Pam', 'Danladi', 'Bitrus', 'Mallo', 'Tarfa', 'Gimba', 'Jatau', 'Kwanashie'],
            ],
            'north_east_west' => [
                'weight' => 4, 'muslim' => 0.9,
                'male' => ['Kyari', 'Mustapha', 'Modu', 'Bukar', 'Goni', 'Lawan', 'Kachalla', 'Ngaski', 'Ahmadu', 'Mahmud', 'Bala', 'Kashim'],
                'female' => ['Falmata', 'Kaka', 'Aisha', 'Zara', 'Fanna', 'Hajja', 'Amina', 'Maimuna', 'Hauwa', 'Binta'],
                'surnames' => ['Shettima', 'Zulum', 'Kyari', 'Gana', 'Mai', 'Gubio', 'Kolo', 'Jega', 'Tukur', 'Mamman', 'Wada', 'Ndayako', 'Kuta', 'Gbadamosi'],
            ],
        ];
    }

    /** Extra Christian "English" names many Nigerians add as a middle name */
    public static function christianMiddleNames(): array
    {
        return [
            'male' => ['Michael', 'David', 'Daniel', 'Emmanuel', 'Samuel', 'Joshua', 'Victor', 'Peter', 'John', 'Joseph', 'Gabriel', 'Isaac', 'Chukwuma', 'Ebube', 'Divine', 'Favour', 'Praise', 'Testimony', 'Mercy', 'Success', 'Wisdom', 'Excel', 'Great'],
            'female' => ['Grace', 'Faith', 'Blessing', 'Joy', 'Mercy', 'Esther', 'Precious', 'Favour', 'Gift', 'Peace', 'Divine', 'Victoria', 'Praise', 'Testimony', 'Treasure', 'Angel', 'Success', 'Hannah', 'Ruth', 'Deborah'],
        ];
    }

    public static function muslimMiddleNames(): array
    {
        return [
            'male' => ['Mohammed', 'Abdulrahman', 'Abdulazeez', 'Olayinka', 'Ayomide', 'Ahmad', 'Muiz', 'Taofeek', 'Ridwan', 'Lukman'],
            'female' => ['Aisha', 'Khadijah', 'Maryam', 'Zainab', 'Nusirat', 'Rukayat', 'Habeebah', 'Sadiyah', 'Fatimah', 'Halimah'],
        ];
    }

    public static function occupations(): array
    {
        return ['Civil Servant', 'Banker', 'Medical Doctor', 'Software Engineer', 'Trader', 'Lawyer', 'Chartered Accountant', 'Oil & Gas Engineer', 'Pastor', 'Business Owner', 'Pharmacist', 'University Lecturer', 'Architect', 'Pilot', 'Nurse', 'Entrepreneur', 'Real Estate Developer', 'Journalist', 'Police Officer', 'Fashion Designer', 'Importer', 'Telecoms Engineer', 'Surveyor', 'Caterer', 'Contractor', 'Imam', 'Customs Officer', 'Dentist', 'Estate Surveyor', 'Brand Consultant'];
    }

    public static function lagosAreas(): array
    {
        return [
            ['Lekki Phase 1', ['Admiralty Way', 'Fola Osibo Road', 'Babatunde Anjous Avenue', 'Ligali Ayorinde Street']],
            ['Ikeja GRA', ['Isaac John Street', 'Oba Akinjobi Way', 'Adeniyi Jones Avenue', 'Mobolaji Bank Anthony Way']],
            ['Magodo Phase 2', ['Shangisha Road', 'Kosoko Street', 'Ajayi Crowther Close', 'Alhaji Jimoh Close']],
            ['Surulere', ['Bode Thomas Street', 'Adeniran Ogunsanya Street', 'Aguda Road', 'Randle Avenue']],
            ['Yaba', ['Herbert Macaulay Way', 'Commercial Avenue', 'Jibowu Street', 'Sabo Road']],
            ['Gbagada', ['Diya Street', 'Idowu Olaitan Street', 'Oworonshoki Expressway', 'Ifako Road']],
            ['Ajah', ['Abraham Adesanya Road', 'Badore Road', 'Sangotedo Estate Road', 'Lekki-Epe Expressway']],
            ['Maryland', ['Mende Road', 'Ikorodu Road', 'Anthony Village Road', 'Opebi Link Road']],
            ['Ikoyi', ['Bourdillon Road', 'Glover Road', 'Gerrard Road', 'Awolowo Road']],
            ['Victoria Island', ['Adeola Odeku Street', 'Ahmadu Bello Way', 'Akin Adesola Street', 'Ozumba Mbadiwe Avenue']],
            ['Ogba', ['Ogba Road', 'Akilo Road', 'Ajayi Road', 'Ikeja-Agege Road']],
            ['Omole Phase 1', ['Alhaji Adeyemi Street', 'Oyetola Close', 'Omole Estate Road', 'Ogundana Street']],
            ['Festac Town', ['2nd Avenue', '4th Avenue', '22 Road', '7th Avenue']],
            ['Ilupeju', ['Ilupeju Industrial Avenue', 'Town Planning Way', 'Palm Avenue', 'Coker Road']],
        ];
    }
}
